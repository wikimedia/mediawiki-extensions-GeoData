<?php

use MediaWiki\Maintenance\Maintenance;

// @codeCoverageIgnoreStart
$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";
// @codeCoverageIgnoreEnd

class UpdateIndexGranularity extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription(
			'Updates GeoData database after $wgGeoDataIndexGranularity has been changed' );
		$this->setBatchSize( 500 );
		$this->requireExtension( 'GeoData' );
	}

	public function execute() {
		$batchSize = $this->getBatchSize();
		$id = 0;
		$dbw = $this->getPrimaryDB();

		do {
			$ids = [];

			$this->beginTransaction( $dbw, __METHOD__ );
			$res = $dbw->newSelectQueryBuilder()
				->select( 'gt_id' )
				->from( 'geo_tags' )
				->where( $dbw->expr( 'gt_id', '>', $id ) )
				->limit( $batchSize )
				->caller( __METHOD__ )
				->fetchResultSet();
			foreach ( $res as $row ) {
				$id = $row->gt_id;
				$ids[] = $id;
			}
			$indexGranularity = $this->getConfig()->get( 'GeoDataIndexGranularity' );
			$dbw->newUpdateQueryBuilder()
				->update( 'geo_tags' )
				->set( [
					"gt_lat_int = ROUND(gt_lat * $indexGranularity)",
					"gt_lon_int = ROUND(gt_lon * $indexGranularity)"
				] )
				->where( [ 'gt_id' => $ids ] )
				->caller( __METHOD__ )
				->execute();
			$this->commitTransaction( $dbw, __METHOD__ );

			$this->output( "$id\n" );
		} while ( count( $ids ) === $batchSize );
	}
}

// @codeCoverageIgnoreStart
$maintClass = UpdateIndexGranularity::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
