<?php

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = dirname( __FILE__ ) . '/../..';
}
require_once( "$IP/maintenance/Maintenance.php" );

class SolrUpdate extends Maintenance {
	const WRITE_BATCH_SIZE = 500;
	const READ_BATCH_SIZE = 1000;
	const READ_DELAY = 0; // In microseconds

	private $jobMode = false;

	public function __construct() {
		$this->mDescription = 'Performs updates and other operations with Solr index';
		$this->addOption( 'reset', 'Reset last update timestamp (next feed will return whole database)' );
		$this->addOption( 'clear-killlist', 'Purge killlist entries older than this value (in days)', false, true );
		$this->addOption( 'noindex', 'Don\'t update index' );
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
		global $wgGeoDataBackend, $wgGeoDataSolrCommitPolicy;
		if ( $wgGeoDataBackend != 'solr' ) {
			$this->error( "This script is only for wikis with Solr GeoData backend", true );
		}

		$dbr = $this->getDB( DB_SLAVE );
		$dbw = $this->getDB( DB_MASTER );

		$wikiId = wfWikiID();

		if ( $this->hasOption( 'reset' ) ) {
			$this->output( "Resetting update tracking...\n" );
			$dbw->delete( 'geo_updates', array( 'gu_wiki' => $wikiId ), __METHOD__ );
			$this->output( "Truncating killlist...\n" );
			$table = $dbw->tableName( 'geo_killlist' );
			$dbw->query( "TRUNCATE TABLE $table", __METHOD__ );
			$cutoffKilllist = false;
		} else {
			$cutoffKilllist = $dbr->selectField( 'geo_killlist', 'MAX( gk_killed_id )', '', __METHOD__ );
		}
		$cutoffTags = $dbr->selectField( 'geo_tags', 'MAX( gt_id )', '', __METHOD__ );

		if ( $this->hasOption( 'clear-killlist' ) ) {
			$days = intval( $this->getOption( 'clear-killlist' ) );
			if ( $days <= 0 ) {
				$this->error( '--clear-killlist: please specify a positive integer number of days', true );
			}
			$this->output( "Deleting killlist entries older than $days days...\n" );
			$timestamp = $dbw->addQuotes( wfTimestamp( TS_DB, strtotime( "$days days ago" ) ) );
			$table = $dbr->tableName( 'geo_killlist' );
			$count = 0;
			do {
				$sql = "DELETE FROM $table WHERE gk_touched < $timestamp LIMIT "
					. self::WRITE_BATCH_SIZE;
				$dbw->query( $sql, __METHOD__ );
				$deleted = $dbw->affectedRows();
				$count += $deleted;
				if ( $deleted ) {
					wfWaitForSlaves();
					$this->output( "  $count\n" );
				}
			} while ( $deleted > 0 );
		}

		if ( $this->hasOption( 'noindex' ) ) {
			return;
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
					$update->addDocuments( $docs, null, $this->commitWithin() );
					$this->addCommit( $update );
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
					if ( $wgGeoDataSolrCommitPolicy === 'immediate' ) {
						$update->addCommit();
					}
					$solrResult = $solr->update( $update );
					wfDebugLog( 'geodata', "Deleting " . count( $killedIds ) . " docs, response: {$solrResult->getResponse()->getBody()}" );

					$count += count( $killedIds );
					$this->output( "   $count\n" );
					usleep( self::READ_DELAY );
				}
			} while ( $res->numRows() > 0 );
			// delete queries don't support commitWithin, so if we're in commitWithin mode,
			// just commit after we're done deleting
			if ( $count && is_int( $wgGeoDataSolrCommitPolicy ) ) {
				$update = $solr->createUpdate();
				$update->addCommit();
				$solr->update( $update );
			}
		}

		$dbw->replace( 'geo_updates',
			array( 'gu_wiki' ),
			array( 'gu_wiki' => $wikiId, 'gu_last_tag' => $lastTag, 'gu_last_kill' => $lastKill ),
			__METHOD__
		);
	}

	/**
	 * @param Solarium_Query_Update $update
	 */
	private function addCommit( $update ) {
		global $wgGeoDataSolrCommitPolicy;

		if ( $wgGeoDataSolrCommitPolicy === 'immediate' ) {
			$update->addCommit();
		} elseif ( !( is_int( $wgGeoDataSolrCommitPolicy ) && $wgGeoDataSolrCommitPolicy > 0 )
			&& $wgGeoDataSolrCommitPolicy !== 'never' ) {
			throw new MWException( "'$wgGeoDataSolrCommitPolicy' is not a valid \$wgGeoDataSolrCommitPolicy value" );
		}
	}

	/**
	 * @return int|null: Number of milliseconds to commit within or null if not applicable
	 */
	private function commitWithin() {
		global $wgGeoDataSolrCommitPolicy;

		if ( is_int( $wgGeoDataSolrCommitPolicy ) ) {
			return $wgGeoDataSolrCommitPolicy;
		}
		return null;
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
