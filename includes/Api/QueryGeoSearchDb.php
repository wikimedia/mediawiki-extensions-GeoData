<?php

namespace GeoData\Api;

use ApiPageSet;
use ApiQuery;
use GeoData\Coord;
use GeoData\Globe;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class QueryGeoSearchDb extends QueryGeoSearch {

	public function __construct( ApiQuery $query, string $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	/**
	 * @param ApiPageSet|null $resultPageSet
	 */
	protected function run( $resultPageSet = null ): void {
		parent::run( $resultPageSet );
		$params = $this->extractRequestParams();

		if ( $params['sort'] === 'relevance' ) {
			$this->dieWithError( 'apierror-geodata-norelevancesort', 'no-relevance-sort' );
		}

		$this->addTables( 'geo_tags' );
		$this->addFields( [ 'gt_lat', 'gt_lon', 'gt_primary' ] );
		foreach ( $params['prop'] as $prop ) {
			if ( isset( Coord::FIELD_MAPPING[$prop] ) ) {
				$this->addFields( Coord::FIELD_MAPPING[$prop] );
			}
		}
		$this->addWhereFld( 'gt_globe', $this->coord->globe );
		$this->addWhere( 'gt_page_id = page_id' );
		$dbr = $this->getDB();
		if ( $this->idToExclude ) {
			$this->addWhere( $dbr->expr( 'gt_page_id', '!=', $this->idToExclude ) );
		}
		if ( isset( $params['maxdim'] ) ) {
			$this->addWhere( $dbr->expr( 'gt_dim', '<', intval( $params['maxdim'] ) ) );
		}
		$primary = $params['primary'];
		$this->addWhereIf( [ 'gt_primary' => intval( $primary === 'primary' ) ], $primary !== 'all' );

		$this->addCoordFilter();

		$limit = $params['limit'];

		$res = $this->select( __METHOD__ );

		$rows = [];
		foreach ( $res as $row ) {
			$row->dist = $this->coord->distanceTo( Coord::newFromRow( $row ) );
			$rows[] = $row;
		}
		// sort in PHP because sorting via SQL would involve a filesort
		usort( $rows, static function ( $row1, $row2 ) {
			return $row1->dist - $row2->dist;
		} );
		$result = $this->getResult();
		foreach ( $rows as $row ) {
			if ( !$limit-- ) {
				break;
			}
			if ( $resultPageSet === null ) {
				$title = Title::newFromRow( $row );
				$vals = [
					'pageid' => intval( $row->page_id ),
					'ns' => $title->getNamespace(),
					'title' => $title->getPrefixedText(),
					'lat' => floatval( $row->gt_lat ),
					'lon' => floatval( $row->gt_lon ),
					'dist' => round( $row->dist, 1 ),
					'primary' => boolval( $row->gt_primary ),
				];
				foreach ( $params['prop'] as $prop ) {
					$column = Coord::FIELD_MAPPING[$prop] ?? null;
					if ( $column && isset( $row->$column ) ) {
						// Don't output default globe
						if ( !( $prop === 'globe' && $row->$column === Globe::EARTH ) ) {
							$vals[$prop] = $row->$column;
						}
					}
				}
				$fit = $result->addValue( [ 'query', $this->getModuleName() ], null, $vals );
				if ( !$fit ) {
					break;
				}
			} else {
				$resultPageSet->processDbRow( $row );
			}
		}
	}

	protected function addCoordFilter(): void {
		$bbox = $this->bbox ?: $this->coord->bboxAround( $this->radius );
		$coord1 = $bbox->topLeft();
		$coord2 = $bbox->bottomRight();
		$this->addWhereFld( 'gt_lat_int', self::intRange( $coord1->lat, $coord2->lat ) );
		$this->addWhereFld( 'gt_lon_int', self::intRange( $coord1->lon, $coord2->lon ) );

		$this->addWhereRange( 'gt_lat', 'newer', (string)$coord1->lat, (string)$coord2->lat, false );
		if ( $coord1->lon > $coord2->lon ) {
			$this->addWhere( "gt_lon < {$coord2->lon} OR gt_lon > {$coord1->lon}" );
		} else {
			$this->addWhereRange( 'gt_lon', 'newer', (string)$coord1->lon, (string)$coord2->lon, false );
		}
		$this->addOption( 'USE INDEX', [ 'geo_tags' => 'gt_spatial' ] );
	}

	/**
	 * Returns a range of tenths of degree
	 *
	 * @param float $start
	 * @param float $end
	 * @param int|null $granularity Defaults to $wgGeoDataIndexGranularity
	 * @return int[]
	 */
	public static function intRange( float $start, float $end, int $granularity = null ): array {
		if ( !$granularity ) {
			$granularity = MediaWikiServices::getInstance()->getMainConfig()->get( 'GeoDataIndexGranularity' );
		}
		$start = round( $start * $granularity );
		$end = round( $end * $granularity );
		// @todo: works only on Earth
		if ( $start > $end ) {
			return array_merge(
				range( -180 * $granularity, $end ),
				range( $start, 180 * $granularity )
			);
		} else {
			return range( $start, $end );
		}
	}
}
