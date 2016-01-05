<?php

namespace GeoData;

use ApiPageSet;
use MWNamespace;
use Title;

class ApiQueryGeoSearchElastic extends ApiQueryGeoSearch {
	private $params;

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	/**
	 * @param ApiPageSet $resultPageSet
	 */
	protected function run( $resultPageSet = null ) {
		global $wgDefaultGlobe, $wgGeoDataIndexLatLon;

		parent::run( $resultPageSet );
		$this->resetQueryParams(); //@fixme: refactor to make this unnecessary

		$params = $this->params = $this->extractRequestParams();

		$bools = new \Elastica\Filter\BoolFilter();
		if ( $this->idToExclude ) {
			$bools->addMustNot( new \Elastica\Filter\Term( [ '_id' => $this->idToExclude ] ) );
		}
		// Only Earth is supported
		$bools->addMust( new \Elastica\Filter\Term( [ 'coordinates.globe' => 'earth' ] ) );
		if ( isset( $params['maxdim'] ) ) {
			$bools->addMust( new \Elastica\Filter\Range( 'coordinates.dim',
					[ 'to' => $params['maxdim'] ] ) );
		}

		$primary = $params['primary'];
		if ( $primary !== 'all' ) {
			$bools->addMust( new \Elastica\Filter\Term( [
					'coordinates.primary' => intval( $primary === 'primary' )
				] ) );
		}
		if ( $this->bbox ) {
			$distanceFilter = new \Elastica\Filter\GeoBoundingBox( 'coordinates.coord', [
					[ 'lat' => $this->bbox->lat1, 'lon' => $this->bbox->lon1 ],
					[ 'lat' => $this->bbox->lat2, 'lon' => $this->bbox->lon2 ],
				] );
		} else {
			$distanceFilter =
				new \Elastica\Filter\GeoDistance( 'coordinates.coord',
					[ 'lat' => $this->coord->lat, 'lon' => $this->coord->lon ],
					$this->radius . 'm' );
			if ( $wgGeoDataIndexLatLon ) {
				$distanceFilter->setOptimizeBbox( 'indexed' );
			}
		}

		$query = new \Elastica\Query();
		$fields =
			array_map( function ( $prop ) { return "coordinates.$prop"; },
				array_merge( [ 'coord', 'primary' ], $params['prop'] ) );
		$query->setParam( '_source', $fields );
		$filter = new \Elastica\Filter\BoolAnd();
		$filter->addFilter( $bools );
		$filter->addFilter( $distanceFilter );
		$nested = new \Elastica\Filter\Nested();
		$nested->setPath( 'coordinates' )->setFilter( $filter );
		if ( count( $params['namespace'] ) < count( MWNamespace::getValidNamespaces() ) ) {
			$outerFilter = new \Elastica\Filter\BoolFilter();
			$outerFilter->addMust( $nested );
			$outerFilter->addMust( new \Elastica\Filter\Terms( 'namespace',
					$params['namespace'] ) );
			$query->setPostFilter( $outerFilter );
		} else {
			$query->setPostFilter( $nested );
		}

		$query->addSort( [
				'_geo_distance' => [
					'coordinates.coord' => [
						'lat' => $this->coord->lat,
						'lon' => $this->coord->lon
					],
					'order' => 'asc',
					'unit' => 'm'
				]
			] );
		$query->setSize( $params['limit'] );

		$searcher = new Searcher( $this->getUser() );

		$resultSet = $searcher->performSearch( $query, 'GeoData_spatial_search' );

		if ( isset( $params['debug'] ) && $params['debug'] ) {
			$this->addDebugInfo( $resultSet );
		}

		$data = $resultSet->getResponse()->getData();

		if ( !isset( $data['hits']['hits'] ) ) {
			$this->dieDebug( __METHOD__, 'Unexpected result set returned by Elasticsearch' );
		}
		$ids = [];
		$coordinates = [];
		foreach ( $data['hits']['hits'] as $page ) {
			$id = $page['_id'];
			foreach ( $page['_source']['coordinates'] as $coordArray ) {
				$coord = $this->makeCoord( $coordArray );
				if ( !$this->filterCoord( $coord ) ) {
					continue;
				}
				$coord->pageId = $id;
				$coordinates[] = $coord;
				$ids[$id] = true;
			}
		}
		usort( $coordinates, function ( $coord1, $coord2 ) {
			if ( $coord1->distance == $coord2->distance ) {
				return 0;
			}
			return ( $coord1->distance < $coord2->distance ) ? - 1 : 1;
		} );

		if ( !count( $coordinates ) ) {
			return; // No results, no point in doing anything else
		}
		$this->addWhere( [ 'page_id' => array_keys( $ids ) ] );
		$this->addTables( 'page' );
		if ( is_null( $resultPageSet ) ) {
			$this->addFields( [ 'page_id', 'page_title', 'page_namespace' ] );
		} else {
			$this->addFields( $resultPageSet->getPageTableFields() );
		}

		$res = $this->select( __METHOD__ );

		if ( is_null( $resultPageSet ) ) {
			$titles = [];
			foreach ( $res as $row ) {
				$titles[$row->page_id] = Title::newFromRow( $row );
			}

			$limit = $params['limit'];
			$result = $this->getResult();

			foreach ( $coordinates as $coord ) {
				if ( !$limit -- ) {
					break;
				}
				$id = $coord->pageId;
				if ( !isset( $titles[$id] ) ) {
					continue;
				}
				/** @var Title $title */
				$title = $titles[$id];
				$vals = [
					'pageid' => intval( $coord->pageId ),
					'ns' => intval( $title->getNamespace() ),
					'title' => $title->getPrefixedText(),
					'lat' => floatval( $coord->lat ),
					'lon' => floatval( $coord->lon ),
					'dist' => round( $coord->distance, 1 ),
				];

				if ( $coord->primary ) {
					$vals['primary'] = '';
				}
				foreach ( $params['prop'] as $prop ) {
					// Don't output default globe
					if ( !( $prop === 'globe' && $coord->$prop === $wgDefaultGlobe ) ) {
						$vals[$prop] = $coord->$prop;
					}
				}
				$fit = $result->addValue( [ 'query', $this->getModuleName() ], null, $vals );
				if ( !$fit ) {
					break;
				}
			}
		} else {
			$resultPageSet->populateFromQueryResult( $this->getDB(), $res );
		}
	}

	/**
	 * Creates a Coord class instance from an array returned by search
	 *
	 * @param array $hit: Search hit
	 *
	 * @return Coord
	 */
	private function makeCoord( array $hit ) {
		$lat = $hit['coord']['lat'];
		$lon = $hit['coord']['lon'];
		$coord = new Coord( $lat, $lon );
		foreach ( Coord::getFields() as $field ) {
			if ( isset( $hit[$field] ) ) {
				$coord->$field = $hit[$field];
			}
		}
		$coord->distance = $this->coord->distanceTo( $coord );
		return $coord;
	}

	/**
	 * Checks whether given coordinates fall within the requested limits
	 * @param Coord $coord
	 *
	 * @return bool: If false, these coordinates should be discarded
	 */
	private function filterCoord( Coord $coord ) {
		if ( !$this->bbox && $coord->distance > $this->radius ) {
			return false;
		}
		// Only one globe is supported for search, this is future-proof
		if ( $coord->globe != $this->coord->globe ) {
			return false;
		}
		if ( isset( $this->params['maxdim'] ) && $coord->dim > $this->params['maxdim'] ) {
			return false;
		}
		$primary = $this->params['primary'];
		if ( ( $primary == 'primary' && !$coord->primary )
			|| ( $primary == 'secondary' && $coord->primary ) )
		{
			return false;
		}
		return true;
	}

	/**
	 * Adds debug information to API result
	 * @param \Elastica\ResultSet $resultSet
	 */
	private function addDebugInfo( \Elastica\ResultSet $resultSet ) {
		$ti = $resultSet->getResponse()->getTransferInfo();
		$neededData = [
			'url',
			'total_time',
			'namelookup_time',
			'connect_time',
			'pretransfer_time',
			'size_upload',
			'size_download',
			'starttransfer_time',
			'redirect_time',
		];
		$debug = [];
		foreach ( $neededData as $name ) {
			if ( isset( $ti[$name] ) ) {
				$debug[$name] = $ti[$name];
			}
		}
		$this->getResult()->addValue( null, 'geodata-debug', $debug );
	}
}
