<?php

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
		$this->resetQueryParams();

		try {
			$params = $this->params = $this->extractRequestParams();

			$bools = new Elastica\Filter\Bool();
			if ( $this->idToExclude ) {
				$bools->addMustNot(
					new Elastica\Filter\Term( array( '_id' => $this->idToExclude ) )
				);
			}
			// Only Earth is supported
			$bools->addMust( new Elastica\Filter\Term( array( 'coordinates.globe' => 'earth' ) ) );
			if ( isset( $params['maxdim'] ) ) {
				$bools->addMust( new Elastica\Filter\Range(
					'coordinates.dim',
					array( 'to' => $params['maxdim'] ) )
				);
			}

			$primary = $params['primary'];
			if ( $primary !== 'all' ) {
				$bools->addMust( new Elastica\Filter\Term(
					array( 'coordinates.primary' => intval( $primary === 'primary' ) )
				) );
			}
			$distanceFilter = new Elastica\Filter\GeoDistance( 'coordinates.coord',
				array( 'lat' => $this->lat, 'lon' => $this->lon ),
				$this->radius . 'm'
			);
			if ( $wgGeoDataIndexLatLon ) {
				$distanceFilter->setOptimizeBbox( 'indexed' );
			}

			$query = new Elastica\Query();
			$fields = array_map(
				function( $prop ) { return "coordinates.$prop"; },
				array_merge( array( 'coord', 'primary' ), $params['prop'] )
			);
			$query->setParam( '_source', $fields );
			$filter = new Elastica\Filter\BoolAnd();
			$filter->addFilter( $bools );
			$filter->addFilter( $distanceFilter );
			$nested = new Elastica\Filter\Nested();
			$nested->setPath( 'coordinates' )
				->setFilter( $filter );
			if ( count( $params['namespace'] ) < count( MWNamespace::getValidNamespaces() ) ) {
				$outerFilter = new Elastica\Filter\Bool();
				$outerFilter->addMust( $nested );
				$outerFilter->addMust(
					new Elastica\Filter\Terms( 'namespace', $params['namespace'] )
				);
				$query->setFilter( $outerFilter );
			} else {
				$query->setFilter( $nested );
			}

			$query->addSort(
				array(
					'_geo_distance' => array(
						'coordinates.coord' => array( 'lat' => $this->lat, 'lon' => $this->lon ),
						'order' => 'asc',
						'unit' => 'm'
					)
				)
			);
			$query->setSize( $params['limit'] );

			$pageType = CirrusSearch\Connection::getPageType( wfWikiID() );
			$search = $pageType->createSearch( $query );

			$resultSet = $search->search();

			if ( isset( $params['debug'] ) && $params['debug'] ) {
				$this->addDebugInfo( $resultSet );
			}

			$data = $resultSet->getResponse()->getData();

			if ( !isset( $data['hits']['hits'] ) ) {
				$this->dieDebug( __METHOD__, 'Unexpected result set returned by Elasticsearch' );
			}
			$ids = array();
			$coordinates = array();
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
			usort( $coordinates, function( $coord1, $coord2 ) {
				if ( $coord1->distance == $coord2->distance ) {
					return 0;
				}
				return ( $coord1->distance < $coord2->distance ) ? -1 : 1;
			} );

			if ( !count( $coordinates ) ) {
				return; // No results, no point in doing anything else
			}
			$this->addWhere( array( 'page_id' => array_keys( $ids ) ) );
			$this->addTables( 'page' );
			if ( is_null( $resultPageSet ) ) {
				$this->addFields( array( 'page_id', 'page_title', 'page_namespace' ) );
			} else {
				$this->addFields( $resultPageSet->getPageTableFields() );
			}

			$res = $this->select( __METHOD__ );

			if ( is_null( $resultPageSet ) ) {
				$titles = array();
				foreach ( $res as $row ) {
					$titles[$row->page_id] = Title::newFromRow( $row );
				}

				$limit = $params['limit'];
				$result = $this->getResult();

				foreach ( $coordinates as $coord ) {
					if ( !$limit-- ) {
						break;
					}
					$id = $coord->pageId;
					if ( !isset( $titles[$id] ) ) {
						continue;
					}
					$title = $titles[$id];
					$vals = array(
						'pageid' => intval( $coord->pageId ),
						'ns' => intval( $title->getNamespace() ),
						'title' => $title->getPrefixedText(),
						'lat' => floatval( $coord->lat ),
						'lon' => floatval( $coord->lon ),
						'dist' => round( $coord->distance, 1 ),
					);

					if ( $coord->primary ) {
						$vals['primary'] = '';
					}
					foreach( $params['prop'] as $prop ) {
						// Don't output default globe
						if ( !( $prop === 'globe' && $coord->$prop === $wgDefaultGlobe ) ) {
							$vals[$prop] = $coord->$prop;
						}
					}
					$fit = $result->addValue(
						array( 'query', $this->getModuleName() ),
						null,
						$vals
					);
					if ( !$fit ) {
						break;
					}
				}
			} else {
				$resultPageSet->populateFromQueryResult( $this->getDB(), $res );
			}
		} catch ( Elastica\Exception\ExceptionInterface $e ) {
			throw new MWException( get_class( $e )
				. " at {$e->getFile()}, line {$e->getLine()}: {$e->getMessage()}", 0, $e
			);
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
		$coord->distance =
			GeoDataMath::distance( $this->lat, $this->lon, $coord->lat, $coord->lon );
		return $coord;
	}

	/**
	 * Checks whether given coordinates fall within the requested limits
	 * @param Coord $coord
	 *
	 * @return bool: If false, these coordinates should be discarded
	 */
	private function filterCoord( Coord $coord ) {
		if ( $coord->distance > $this->radius ) {
			return false;
		}
		// Only one globe is supported for search, this is future-proof
		if ( $coord->globe != $this->params['globe'] ) {
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
		$neededData = array(
			'url',
			'total_time',
			'namelookup_time',
			'connect_time',
			'pretransfer_time',
			'size_upload',
			'size_download',
			'starttransfer_time',
			'redirect_time',
		);
		$debug = array();
		foreach ( $neededData as $name ) {
			if ( isset( $ti[$name] ) ) {
				$debug[$name] = $ti[$name];
			}
		}
		$this->getResult()->addValue( null, 'geodata-debug', $debug );
	}
}
