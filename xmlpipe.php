<?php

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = dirname( __FILE__ ) . '/../..';
}
require_once( "$IP/maintenance/Maintenance.php" );

class FeedXmlPipe extends Maintenance {
	const WRITE_BATCH_SIZE = 500;
	const READ_BATCH_SIZE = 5000;
	const READ_DELAY = 500000; // 500 ms

	public function __construct() {
		$this->mDescription = "Outputs GeoData changes feed in Sphinx search's xmlpipe2 format";
		$this->addOption( 'feed', 'Output feed' );
		$this->addOption( 'reset', 'Reset last update timestamp (next feed will return whole database)' );
		$this->addOption( 'clean-killlist', 'Purge killlist entries older than this value (in days)', false, true );
		$this->addOption( 'host', 'Retrieve feed for this Sphinx host ("-" to use current hostname)', false, true );
	}

	public function execute() {
		$dbw = $this->getDB( DB_MASTER );
		$operations = 0;

		$wikiId = wfWikiID();
		if ( $this->hasOption( 'host' ) ) {
			$wikiId .= '-';
			if ( $this->getOption( 'host' == '-' ) ) {
				$wikiId .= gethostname();
			} else {
				$wikiId .= $this->getOption( 'host' );
			}
		}

		if ( $this->hasOption( 'reset' ) ) {
			$operations++;
			$dbw->delete( 'geo_updates', array( 'gu_wiki' => $wikiId ), __METHOD__ );
		}

		if ( $this->hasOption( 'clean-killlist' ) ) {
			$operations++;
			do {
				$days = intval( $this->getOption( 'clean-killlist' ) );
				if ( $days <= 0 ) {
					$this->error( '--clean-killlist: please specify a positive integer number of days', true );
				}
				$table = $dbw->tableName( 'geo_killlist' );
				$sql = "DELETE FROM $table WHERE gk_touched < ADDDATE( CURRENT_TIMESTAMP, INTERVAL -$days DAY ) LIMIT "
					. self::WRITE_BATCH_SIZE;
				$res = $dbw->query( $sql, __METHOD__ );
				wfWaitForSlaves();
			} while ( $res->numRows() > 0 );
		}

		if ( $this->hasOption( 'feed' ) ) {
			$operations++;
			$dbr = $this->getDB( DB_SLAVE );
			$lastUpdate = $dbw->selectField( 'geo_updates',
				'gu_last_update',
				array( 'gu_wiki' => $wikiId ),
				__METHOD__
			);
			// @todo: geo_tags could be outta sync with geo_killlist due to replication
			$cutoffTags = $dbr->selectField( 'geo_tags', 'MAX( gt_touched )', '', __METHOD__ );
			$cutoffKilllist = $dbr->selectField( 'geo_killlist', 'MAX( gk_touched )', '', __METHOD__ );
			if ( wfGetLB()->getServerCount() > 1 ) {
				// let changes made on the same second as $cutoff propagate
				sleep( 1 );
				wfWaitForSlaves();
			}
			$cutoff = max( $cutoffTags, $cutoffKilllist );
			$range = $lastUpdate ? ( 'gt_touched > ' . $dbr->addQuotes( $lastUpdate ) ) : '1';
			echo XmlPipe::beginStream();
			do {
				$conds = array(
					'gt_touched <= ' . $dbr->addQuotes( $cutoff ),
					$range,
					'gt_globe' => 'earth',
				);
				$res = $dbr->select( 'geo_tags',
					array( 'gt_id', 'gt_touched', 'gt_lat', 'gt_lon' ),
					$conds,
					__METHOD__,
					array( 'LIMIT' => self::READ_BATCH_SIZE, 'ORDER BY' => array( 'gt_touched', 'gt_id' ) )
				);
				foreach ( $res as $row ) {
					echo XmlPipe::formatRow( $row );
				}
				if ( $res->numRows() ) {
					$range = 'gt_touched >= ' . $dbr->addQuotes( $row->gt_touched ) . " AND gt_id > {$row->gt_id}";
					usleep( self::READ_DELAY );
				}
			} while ( $res->numRows() > 0 );

			// no need for killlist during initial indexing
			if ( $lastUpdate ) {
				echo XmlPipe::beginKillList();
				$range = $lastUpdate ? ( 'gk_touched > ' . $dbr->addQuotes( $lastUpdate ) ) : '1';
				do {
					$conds = array(
						'gk_touched <= ' . $dbr->addQuotes( $cutoff ),
						$range,
					);
					$res = $dbr->select( 'geo_killlist',
						array( 'gk_id', 'gk_touched' ),
						$conds,
						__METHOD__,
						array( 'LIMIT' => self::READ_BATCH_SIZE, 'ORDER BY' => array( 'gk_touched', 'gk_id' ) )
					);
					foreach ( $res as $row ) {
						echo XmlPipe::killId( $row );
					}
					if ( $res->numRows() ) {
						$range = 'gk_touched >= ' . $dbr->addQuotes( $row->gk_touched ) . " AND gk_id > {$row->gk_id}";
						usleep( self::READ_DELAY );
					}
				} while ( $res->numRows() > 0 );
				echo XmlPipe::closeKillList();
			}

			echo XmlPipe::closeStream();
			$dbw->replace( 'geo_updates',
				array( 'gu_wiki' ),
				array( 'gu_wiki' => $wikiId, 'gu_last_update' => $cutoff ),
				__METHOD__
			);
		}

		if ( $operations == 0 ) {
			$this->maybeHelp( true );
		}
	}
}

/**
 * xmlpipe2 formatter
 */
class XmlPipe {
	public static function beginStream() {
		return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<sphinx:docset>
	<sphinx:schema>
		<sphinx:field name="tiles"/>
		<sphinx:attr name="lat" type="float"/>
		<sphinx:attr name="lon" type="float"/>
	</sphinx:schema>

XML;
	}

	public static function formatRow( $row ) {
		$tiles = 'LAT' . round( $row->gt_lat * 10 ) . ' ' . 'LON' . round( $row->gt_lon * 10 );
		return Xml::openElement( 'sphinx:document', array( 'id' => $row->gt_id ) )
			. Xml::element( 'tiles', null, $tiles )
			. Xml::element( 'lat', null, deg2rad( $row->gt_lat ) )
			. Xml::element( 'lon', null, deg2rad( $row->gt_lon ) )
			. Xml::closeElement( 'sphinx:document' )
			. "\n";
	}

	public static function beginKillList() {
		return "<sphinx:killlist>\n";
	}

	public static function killId( $row ) {
		return Xml::element( 'id', null, $row->gk_id ) . "\n";
	}

	public static function closeKillList() {
		return "</sphinx:killlist>\n";
	}

	public static function closeStream() {
		return "</sphinx:docset>";
	}
}

$maintClass = 'FeedXmlPipe';
require_once( DO_MAINTENANCE );
