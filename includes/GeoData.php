<?php

namespace GeoData;

use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\IDatabase;

class GeoData {
	/**
	 * Returns primary coordinates of the given page, if any
	 * @param int $pageId
	 * @return Coord|bool Coordinates or false
	 */
	public static function getPageCoordinates( int $pageId ) {
		$coords = self::getAllCoordinates( $pageId, [ 'gt_primary' => 1 ] );
		return $coords ? $coords[0] : false;
	}

	/**
	 * Retrieves all coordinates for the given page id
	 *
	 * @param int $pageId ID of the page
	 * @param array $conds Conditions for IDatabase::select()
	 * @param int $dbType Database to select from DB_PRIMARY or DB_REPLICA
	 * @return Coord[]
	 */
	public static function getAllCoordinates( int $pageId, array $conds = [], int $dbType = DB_REPLICA ): array {
		$db = self::getDB( $dbType );
		$conds['gt_page_id'] = $pageId;
		$columns = array_values( Coord::FIELD_MAPPING );
		$res = $db->select( 'geo_tags', $columns, $conds, __METHOD__ );
		$coords = [];
		foreach ( $res as $row ) {
			$coords[] = Coord::newFromRow( $row );
		}
		return $coords;
	}

	/**
	 * @param int $dbType DB_PRIMARY or DB_REPLICA
	 * @return IDatabase
	 */
	private static function getDB( int $dbType ): IDatabase {
		return MediaWikiServices::getInstance()
			->getDBLoadBalancer()
			->getConnection( $dbType );
	}
}
