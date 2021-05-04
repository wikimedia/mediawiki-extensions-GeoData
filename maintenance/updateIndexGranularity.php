<?php

use MediaWiki\MediaWikiServices;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class UpdateIndexGranularity extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription(
			'Updates GeoData database after $wgGeoDataIndexGranularity has been changed' );
		$this->setBatchSize( 500 );
		$this->requireExtension( 'GeoData' );
	}

	public function execute() {
		global $wgGeoDataIndexGranularity;

		$batchSize = $this->getBatchSize();
		$id = 0;
		$dbw = $this->getDB( DB_PRIMARY );
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

		do {
			$ids = [];

			$this->beginTransaction( $dbw, __METHOD__ );
			$res = $dbw->select( 'geo_tags', 'gt_id',
				[ "gt_id > $id" ],
				__METHOD__,
				[ 'LIMIT' => $batchSize ]
			);
			foreach ( $res as $row ) {
				$id = $row->gt_id;
				$ids[] = $id;
			}
			$dbw->update( 'geo_tags',
				[
					"gt_lat_int = ROUND(gt_lat * $wgGeoDataIndexGranularity)",
					"gt_lon_int = ROUND(gt_lon * $wgGeoDataIndexGranularity)"
				],
				[ 'gt_id' => $ids ],
				__METHOD__
			);
			$this->commitTransaction( $dbw, __METHOD__ );

			$this->output( "$id\n" );
			$lbFactory->waitForReplication();
		} while ( count( $ids ) === $batchSize );
	}
}

$maintClass = UpdateIndexGranularity::class;
require_once RUN_MAINTENANCE_IF_MAIN;
