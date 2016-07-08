<?php

namespace GeoData;

use ApiPageSet;
use FormatJson;
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
		global $wgDefaultGlobe;

		parent::run( $resultPageSet );
		$this->resetQueryParams(); //@fixme: refactor to make this unnecessary

		$params = $this->params = $this->extractRequestParams();
		$namespaces = array_map( 'intval', $params['namespace'] );

		$filter = new \Elastica\Query\BoolQuery();
		$nestedPropsFilter = new \Elastica\Query\BoolQuery();

		if ( $this->idToExclude ) {
			$filter->addMustNot( new \Elastica\Query\Term( [ '_id' => $this->idToExclude ] ) );
		}
		// Only Earth is supported
		$nestedPropsFilter->addFilter( new \Elastica\Query\Term( [ 'coordinates.globe' => 'earth' ] ) );
		if ( isset( $params['maxdim'] ) ) {
			$nestedPropsFilter->addFilter( new \Elastica\Query\Range( 'coordinates.dim',
					[ 'to' => $params['maxdim'] ] ) );
		}

		$primary = $params['primary'];
		if ( $primary !== 'all' ) {
			$nestedPropsFilter->addFilter( new \Elastica\Query\Term( [
					'coordinates.primary' => intval( $primary === 'primary' )
				] ) );
		}

		if ( $this->bbox ) {
			$distanceFilter = new \Elastica\Query\GeoBoundingBox( 'coordinates.coord', [
					[ 'lat' => $this->bbox->lat1, 'lon' => $this->bbox->lon1 ],
					[ 'lat' => $this->bbox->lat2, 'lon' => $this->bbox->lon2 ],
				] );
		} else {
			$distanceFilter =
				new \Elastica\Query\GeoDistance( 'coordinates.coord',
					[ 'lat' => $this->coord->lat, 'lon' => $this->coord->lon ],
					$this->radius . 'm' );
			$distanceFilter->setOptimizeBbox( 'indexed' );
		}

		$filter->addFilter( $nestedPropsFilter );
		$filter->addFilter( $distanceFilter );

		$query = new \Elastica\Query();
		$fields =
			array_map( function ( $prop ) { return "coordinates.$prop"; },
				array_merge( [ 'coord', 'primary' ], $params['prop'] ) );
		$query->setParam( '_source', $fields );

		$nested = new \Elastica\Query\Nested();
		$nested->setPath( 'coordinates' )->setQuery( $filter );
		if ( count( $namespaces ) < count( MWNamespace::getValidNamespaces() ) ) {
			$outerFilter = new \Elastica\Query\BoolQuery();
			$outerFilter->addFilter( $nested );
			$outerFilter->addFilter( new \Elastica\Query\Terms( 'namespace', $namespaces ) );
			$query->setPostFilter( $outerFilter );
		} else {
			$query->setPostFilter( $nested );
		}

		$query->addSort( [
				'_geo_distance' => [
					'nested_path' => 'coordinates',
					'nested_filter' => $nestedPropsFilter->toArray(),
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

		$resultSet = $searcher->performSearch( $query, $namespaces, 'GeoData_spatial_search' );

		if ( isset( $params['debug'] ) && $params['debug'] ) {
			$this->addDebugInfo( $resultSet, $query );
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
			$res->rewind();
			foreach ( $res as $row ) {
				$title = Title::newFromRow( $row );
				$resultPageSet->setGeneratorData( $title, [ 'index' => $res->key() - 1 ] );
			}
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
	 * @param \Elastica\Query $query
	 */
	private function addDebugInfo( \Elastica\ResultSet $resultSet, \Elastica\Query $query ) {
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
		$debug = [
			'query' => FormatJson::encode( $query->toArray(), true, FormatJson::UTF8_OK ),
		];
		foreach ( $neededData as $name ) {
			if ( isset( $ti[$name] ) ) {
				$debug[$name] = $ti[$name];
			}
		}
		$this->getResult()->addValue( null, 'geodata-debug', $debug );
	}
}
