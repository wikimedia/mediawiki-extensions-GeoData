<?php

namespace GeoData;

use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\IReadableDatabase;

class GeoData {
	/**
	 * Returns primary coordinates of the given page, if any
	 */
	public static function getPageCoordinates( int $pageId ): ?Coord {
		$coords = self::getAllCoordinates( $pageId, [ 'gt_primary' => 1 ] );
		return $coords[0] ?? null;
	}

	/**
	 * Retrieves all coordinates for the given page id
	 *
	 * @param int $pageId ID of the page
	 * @param array $conds Conditions for {@see IReadableDatabase::select}
	 * @param bool $usePrimary Whether to use the primary database (`true`) or a replica (`false`)
	 * @return Coord[]
	 */
	public static function getAllCoordinates( int $pageId, array $conds = [], bool $usePrimary = false ): array {
		$db = self::getDB( $usePrimary );
		$conds['gt_page_id'] = $pageId;
		$columns = array_values( Coord::FIELD_MAPPING );
		$res = $db->newSelectQueryBuilder()
			->select( $columns )
			->from( 'geo_tags' )
			->where( $conds )
			->caller( __METHOD__ )
			->fetchResultSet();
		$coords = [];
		foreach ( $res as $row ) {
			$coords[] = Coord::newFromRow( $row );
		}
		return $coords;
	}

	private static function getDB( bool $usePrimary ): IReadableDatabase {
		$connectionProvider = MediaWikiServices::getInstance()->getConnectionProvider();
		return $usePrimary ? $connectionProvider->getPrimaryDatabase() : $connectionProvider->getReplicaDatabase();
	}
}
