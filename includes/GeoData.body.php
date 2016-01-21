<?php

namespace GeoData;

use Status;
use Title;

class GeoData {
	/**
	 *
	 * @param float $lat
	 * @param float $lon
	 * @param string $globe
	 * @return bool Whether the coordinate is valid
	 */
	public static function validateCoord( $lat, $lon, $globe = 'earth' ) {
		global $wgGlobes;
		if ( !is_numeric( $lat ) || !is_numeric( $lon ) || abs( $lat ) > 90 ) {
			return false;
		}
		if ( !isset( $wgGlobes[$globe] ) ) {
			return abs( $lon ) <= 360;
		} else {
			return $lon >= $wgGlobes[$globe]['min'] && $lon <= $wgGlobes[$globe]['max'];
		}
	}

	/**
	 * Returns primary coordinates of the given page, if any
	 * @param Title $title
	 * @return Coord|bool Coordinates or false
	 */
	public static function getPageCoordinates( Title $title ) {
		$coords = self::getAllCoordinates( $title->getArticleID(), array( 'gt_primary' => 1 ) );
		if ( $coords ) {
			return $coords[0];
		}
		return false;
	}

	/**
	 * Retrieves all coordinates for the given page id
	 *
	 * @param int $pageId ID of the page
	 * @param array $conds Conditions for Database::select()
	 * @param int $dbType Database to select from DM_MASTER or DB_SLAVE
	 * @return Coord[]
	 */
	public static function getAllCoordinates( $pageId, $conds = array(), $dbType = DB_SLAVE ) {
		$db = wfGetDB( $dbType );
		$conds['gt_page_id'] = $pageId;
		$res = $db->select( 'geo_tags', Coord::getColumns(), $conds, __METHOD__ );
		$coords = array();
		foreach ( $res as $row ) {
			$coords[] = Coord::newFromRow( $row );
		}
		return $coords;
	}
}
