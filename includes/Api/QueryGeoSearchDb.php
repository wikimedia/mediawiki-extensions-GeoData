<?php

namespace GeoData\Api;

use ApiPageSet;
use ApiQuery;
use GeoData\Coord;
use GeoData\Math;
use Title;

class QueryGeoSearchDb extends QueryGeoSearch {
	/**
	 * @param ApiQuery $query
	 * @param string $moduleName
	 */
	public function __construct( ApiQuery $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	/**
	 * @param ApiPageSet|null $resultPageSet
	 */
	protected function run( $resultPageSet = null ) {
		global $wgDefaultGlobe;

		parent::run( $resultPageSet );
		$params = $this->extractRequestParams();

		$this->addTables( 'geo_tags' );
		$this->addFields( [ 'gt_lat', 'gt_lon', 'gt_primary' ] );
		$mapping = Coord::getFieldMapping();
		foreach ( $params['prop'] as $prop ) {
			if ( isset( $mapping[$prop] ) ) {
				$this->addFields( $mapping[$prop] );
			}
		}
		$this->addWhereFld( 'gt_globe', $this->coord->globe );
		$this->addWhere( 'gt_page_id = page_id' );
		if ( $this->idToExclude ) {
			$this->addWhere( "gt_page_id <> {$this->idToExclude}" );
		}
		if ( isset( $params['maxdim'] ) ) {
			$this->addWhere( 'gt_dim < ' . intval( $params['maxdim'] ) );
		}
		$primary = $params['primary'];
		$this->addWhereIf( [ 'gt_primary' => intval( $primary === 'primary' ) ], $primary !== 'all' );

		$this->addCoordFilter();

		$limit = $params['limit'];

		$res = $this->select( __METHOD__ );

		$rows = [];
		foreach ( $res as $row ) {
			$row->dist = Math::distance( $this->coord->lat, $this->coord->lon, $row->gt_lat, $row->gt_lon );
			$rows[] = $row;
		}
		// sort in PHP because sorting via SQL would involve a filesort
		usort( $rows, static function ( $row1, $row2 ) {
			if ( $row1->dist == $row2->dist ) {
				return 0;
			}
			return ( $row1->dist < $row2->dist ) ? -1 : 1;
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
					if ( isset( $mapping[$prop] ) && isset( $row->{$mapping[$prop]} ) ) {
						$field = $mapping[$prop];
						// Don't output default globe
						if ( !( $prop === 'globe' && $row->$field === $wgDefaultGlobe ) ) {
							$vals[$prop] = $row->$field;
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

	protected function addCoordFilter() {
		$bbox = $this->bbox ?: $this->coord->bboxAround( $this->radius );
		$this->addWhereFld( 'gt_lat_int', self::intRange( $bbox->lat1, $bbox->lat2 ) );
		$this->addWhereFld( 'gt_lon_int', self::intRange( $bbox->lon1, $bbox->lon2 ) );

		$this->addWhereRange( 'gt_lat', 'newer', (string)$bbox->lat1, (string)$bbox->lat2, false );
		if ( $bbox->lon1 > $bbox->lon2 ) {
			$this->addWhere( "gt_lon < {$bbox->lon2} OR gt_lon > {$bbox->lon1}" );
		} else {
			$this->addWhereRange( 'gt_lon', 'newer', (string)$bbox->lon1, (string)$bbox->lon2, false );
		}
		$this->addOption( 'USE INDEX', [ 'geo_tags' => 'gt_spatial' ] );
	}

	/**
	 * Returns a range of tenths of degree
	 *
	 * @param float $start
	 * @param float $end
	 * @param int|null $granularity
	 *
	 * @return array
	 */
	public static function intRange( $start, $end, $granularity = null ) {
		global $wgGeoDataIndexGranularity;

		if ( !$granularity ) {
			$granularity = $wgGeoDataIndexGranularity;
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
