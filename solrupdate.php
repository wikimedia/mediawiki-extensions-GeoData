<?php

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = dirname( __FILE__ ) . '/../..';
}
require_once( "$IP/maintenance/Maintenance.php" );

class SolrUpdate extends Maintenance {
	const WRITE_BATCH_SIZE = 500;
	const READ_BATCH_SIZE = 5000;
	const READ_DELAY = 500000; // 500 ms

	private $jobMode = false;

	public function __construct() {
		$this->mDescription = 'Updates Solr index';
		$this->addOption( 'reset', 'Reset last update timestamp (next feed will return whole database)' );
		$this->addOption( 'clean-killlist', 'Purge killlist entries older than this value (in days)', false, true );
	}

	public function enableJobMode() {
		$this->mQuiet = true;
		$this->jobMode = true;
	}

	public function execute() {
		// Make sure that the index is being updated only once
		$work = new SolrUpdateWork( $this );
		if ( !$work->execute() ) {
			$this->error( __METHOD__ . '(): PoolCounter error!', true );
		}
	}

	/**
	 * Called internally
	 */
	public function safeExecute() {
		global $wgGeoDataBackend;
		if ( $wgGeoDataBackend != 'solr' ) {
			$this->error( "This script is only for wikis with Solr GeoData backend", true );
		}

		$dbr = $this->getDB( DB_SLAVE );
		$dbw = $this->getDB( DB_MASTER );

		$wikiId = wfWikiID();

		if ( $this->hasOption( 'reset' ) ) {
			$dbw->delete( 'geo_updates', array( 'gu_wiki' => $wikiId ), __METHOD__ );
			return;
		}

		if ( $this->hasOption( 'clean-killlist' ) ) {
			$days = intval( $this->getOption( 'clean-killlist' ) );
			if ( $days <= 0 ) {
				$this->error( '--clean-killlist: please specify a positive integer number of days', true );
			}
			$this->output( "Deleting killlist entries older than $days days...\n" );
			$table = $dbr->tableName( 'geo_killlist' );
			do {
				$sql = "DELETE FROM $table WHERE gk_touched < ADDDATE( CURRENT_TIMESTAMP, INTERVAL -$days DAY ) LIMIT "
					. self::WRITE_BATCH_SIZE;
				$res = $dbw->query( $sql, __METHOD__ );
				wfWaitForSlaves();
			} while ( $res->numRows() != self::WRITE_BATCH_SIZE );
		}

		$res = $dbr->select( 'geo_updates',
			array( 'gu_last_tag', 'gu_last_kill' ),
			array( 'gu_wiki' => $wikiId ),
			__METHOD__
		);
		if ( !$res || !( $row = $res->fetchObject() ) ) {
			$lastTag = $lastKill = 0;
		} else {
			$lastTag = $row->gu_last_tag;
			$lastKill = $row->gu_last_kill;
		}

		$cutoffTags = $dbr->selectField( 'geo_tags', 'MAX( gt_id )', '', __METHOD__ );
		$cutoffKilllist = $dbr->selectField( 'geo_killlist', 'MAX( gk_killed_id )', '', __METHOD__ );

		$solr = SolrGeoData::newClient( 'master' );

		$fields = Coord::$fieldMapping;
		$fields['page_id'] = 'gt_page_id';

		if ( $cutoffTags ) {
			$this->output( "Indexing new documents...\n" );
			$count = 0;
			do {
				$conds = array(
					"gt_id <= $cutoffTags",
					'gt_globe' => 'earth',
				);
				if ( $lastTag ) {
					$conds[] = "gt_id > $lastTag";
				}
				$res = $dbr->select( 'geo_tags',
					array_values( $fields ),
					$conds,
					__METHOD__,
					array( 'LIMIT' => self::READ_BATCH_SIZE, 'ORDER BY' => 'gt_id' )
				);
				$docs = array();
				$update = $solr->createUpdate();
				foreach ( $res as $row ) {
					$lastTag = $row->gt_id;
					$doc = $update->createDocument();
					$row->gt_id = $wikiId . '-' . $row->gt_id;
					foreach( $fields as $solrField => $dbField ) {
						if ( $solrField != 'lat' && $solrField != 'lon' ) {
							$doc->addField( $solrField, $row->$dbField );
						}
					}
					$doc->addField( 'wiki', $wikiId );
					$doc->addField( 'coord', "{$row->gt_lat},{$row->gt_lon}" );
					$docs[] = $doc;
				}
				if ( $docs ) {
					$update->addDocuments( $docs );
					$update->addCommit();
					$solr->update( $update );

					$count += count( $docs );
					$this->output( "   $count\n" );
					usleep( self::READ_DELAY );
				}
			} while ( $res->numRows() > 0 );
		}

		if ( $cutoffKilllist ) {
			$this->output( "Deleting old documents...\n" );
			$count = 0;
			do {
				$conds = array(
					"gk_killed_id <= $cutoffKilllist",
				);
				if ( $lastKill ) {
					$conds[] = "gk_killed_id > $lastKill";
				}
				$res = $dbr->select( 'geo_killlist',
					array( 'gk_killed_id' ),
					$conds,
					__METHOD__,
					array( 'LIMIT' => self::READ_BATCH_SIZE, 'ORDER BY' => 'gk_killed_id' )
				);
				$killedIds = array();
				$update = $solr->createUpdate();
				foreach ( $res as $row ) {
					$lastKill = $row->gk_killed_id;
					$killedIds[] = $wikiId . '-' . $row->gk_killed_id;
				}
				if ( $killedIds ) {
					$update->addDeleteByIds( $killedIds );
					$update->addCommit();
					$solr->update( $update );

					$count += count( $killedIds );
					$this->output( "   $count\n" );
					usleep( self::READ_DELAY );
				}
			} while ( $res->numRows() > 0 );
		}

		$dbw->replace( 'geo_updates',
			array( 'gu_wiki' ),
			array( 'gu_wiki' => $wikiId, 'gu_last_tag' => $lastTag, 'gu_last_kill' => $lastKill ),
			__METHOD__
		);
	}

	/**
	 * Overrides Maintenace::error() to throw exceptions instead of writing to stderr when called from a job
	 * @param String $err
	 * @param int $die
	 */
	protected function error( $err, $die = 0 ) {
		if ( $this->jobMode ) {
			if ( $die ) {
				throw new MWException( $err );
			} else {
				wfDebug( "$err\n" );
			}
		}
		parent::error( $err, $die );
	}
}

$maintClass = 'SolrUpdate';
require_once( DO_MAINTENANCE );
