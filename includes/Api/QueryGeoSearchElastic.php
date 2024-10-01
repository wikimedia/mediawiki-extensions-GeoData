<?php

namespace GeoData\Api;

use ApiPageSet;
use ApiQuery;
use FormatJson;
use GeoData\Coord;
use GeoData\Globe;
use GeoData\Searcher;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class QueryGeoSearchElastic extends QueryGeoSearch {
	/** @var array|null */
	private $params;

	public function __construct( ApiQuery $query, string $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	/**
	 * @param ApiPageSet|null $resultPageSet
	 */
	protected function run( $resultPageSet = null ): void {
		parent::run( $resultPageSet );
		// @fixme: refactor to make this unnecessary
		$this->resetQueryParams();

		$params = $this->params = $this->extractRequestParams();
		$namespaces = array_map( 'intval', $params['namespace'] );

		$filter = new \Elastica\Query\BoolQuery();
		$nestedPropsFilter = new \Elastica\Query\BoolQuery();

		if ( $this->idToExclude ) {
			$filter->addMustNot( new \Elastica\Query\Term( [ '_id' => $this->idToExclude ] ) );
		}
		// Only Earth is supported
		$nestedPropsFilter->addFilter( new \Elastica\Query\Term( [ 'coordinates.globe' => Globe::EARTH ] ) );
		if ( isset( $params['maxdim'] ) ) {
			$nestedPropsFilter->addFilter( new \Elastica\Query\Range( 'coordinates.dim',
					[ 'to' => $params['maxdim'] ] ) );
		}

		$primary = $params['primary'];
		if ( $primary !== 'all' ) {
			$nestedPropsFilter->addFilter( new \Elastica\Query\Term( [
					'coordinates.primary' => $primary === 'primary'
				] ) );
		}

		if ( $this->bbox ) {
			$coord1 = $this->bbox->topLeft();
			$coord2 = $this->bbox->bottomRight();
			$distanceFilter = new \Elastica\Query\GeoBoundingBox( 'coordinates.coord', [
					[ 'lat' => $coord1->lat, 'lon' => $coord1->lon ],
					[ 'lat' => $coord2->lat, 'lon' => $coord2->lon ],
				] );
		} else {
			$distanceFilter =
				new \Elastica\Query\GeoDistance( 'coordinates.coord',
					[ 'lat' => $this->coord->lat, 'lon' => $this->coord->lon ],
					$this->radius . 'm' );
		}

		$filter->addFilter( $nestedPropsFilter );
		$filter->addFilter( $distanceFilter );

		$query = new \Elastica\Query();
		$fields = array_map(
			static function ( $prop ) {
				return "coordinates.$prop";
			},
			array_merge(
				[ 'coord', 'primary' ],
				$params['prop']
			)
		);
		$query->setParam( '_source', $fields );

		$nested = new \Elastica\Query\Nested();
		$nested->setPath( 'coordinates' )->setQuery( $filter );
		if ( count( $namespaces ) <
			count( MediaWikiServices::getInstance()->getNamespaceInfo()->getValidNamespaces() )
		) {
			$outerFilter = new \Elastica\Query\BoolQuery();
			$outerFilter->addFilter( $nested );
			$outerFilter->addFilter( new \Elastica\Query\Terms( 'namespace', $namespaces ) );
			$query->setPostFilter( $outerFilter );
		} else {
			$query->setPostFilter( $nested );
		}

		$searcher = new Searcher( $this->getUser() );

		if ( $params['sort'] === 'relevance' ) {
			// Should be in sync with
			// https://gerrit.wikimedia.org/g/mediawiki/extensions/CirrusSearch/+/ae9c7338/includes/Search/SearchRequestBuilder.php#97
			$rescores = $searcher->getRelevanceRescoreConfigurations( $namespaces );
			if ( $rescores ) {
				$query->setParam( 'rescore', $rescores );
			}
		} else {
			$query->addSort( [
				'_geo_distance' => [
					'nested' => [
						'path' => 'coordinates',
						'filter' => $nestedPropsFilter->toArray(),
					],
					'coordinates.coord' => [
						'lat' => $this->coord->lat,
						'lon' => $this->coord->lon
					],
					'order' => 'asc',
					'unit' => 'm'
				]
			] );
		}

		$query->setSize( $params['limit'] );

		$status = $searcher->performSearch( $query, $namespaces, 'GeoData_spatial_search' );
		if ( !$status->isOk() ) {
			$this->dieStatus( $status );
		}

		$this->addMessagesFromStatus( $status );
		/** @var \Elastica\ResultSet $resultSet */
		$resultSet = $status->getValue();

		if ( isset( $params['debug'] ) && $params['debug'] ) {
			$this->addDebugInfo( $resultSet, $query );
		}

		$data = $resultSet->getResponse()->getData();

		if ( !isset( $data['hits']['hits'] ) ) {
			wfDebugLog( 'CirrusSearch', 'Unexpected result set returned by Elasticsearch', 'all', [
				'elastic_query' => FormatJson::encode( $query->toArray() ),
				'content' => FormatJson::encode( $data ),
			] );
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

		if ( $coordinates === [] ) {
			// No results, no point in doing anything else
			return;
		}

		if ( $params['sort'] === 'distance' ) {
			usort( $coordinates, static function ( $coord1, $coord2 ) {
				return $coord1->distance - $coord2->distance;
			} );
		}

		$this->addWhere( [ 'page_id' => array_keys( $ids ) ] );
		$this->addTables( 'page' );
		if ( $resultPageSet === null ) {
			$this->addFields( [ 'page_id', 'page_title', 'page_namespace' ] );
		} else {
			$this->addFields( $resultPageSet->getPageTableFields() );
		}

		$res = $this->select( __METHOD__ );

		if ( $resultPageSet === null ) {
			/** @var Title[] $titles */
			$titles = [];
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
				$vals = [
					'pageid' => intval( $coord->pageId ),
					'ns' => $title->getNamespace(),
					'title' => $title->getPrefixedText(),
					'lat' => floatval( $coord->lat ),
					'lon' => floatval( $coord->lon ),
					'dist' => round( $coord->distance, 1 ),
					'primary' => boolval( $coord->primary ),
				];

				foreach ( $params['prop'] as $prop ) {
					// Don't output default globe
					if ( !( $prop === 'globe' && $coord->$prop === Globe::EARTH ) ) {
						$vals[$prop] = $coord->$prop;
					}
				}
				$fit = $result->addValue( [ 'query', $this->getModuleName() ], null, $vals );
				if ( !$fit ) {
					break;
				}
			}
		} else {
			$db = $this->getDB();
			$resultPageSet->populateFromQueryResult( $db, $res );
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
	 * @param array $hit Search hit
	 *
	 * @return Coord
	 */
	private function makeCoord( array $hit ): Coord {
		$lat = $hit['coord']['lat'];
		$lon = $hit['coord']['lon'];
		$globe = $hit['coord']['globe'] ?? Globe::EARTH;
		$coord = new Coord( $lat, $lon, $globe );
		foreach ( Coord::FIELD_MAPPING as $field => $_ ) {
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
	 * @return bool If false these coordinates should be discarded
	 */
	private function filterCoord( Coord $coord ): bool {
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
		// @phan-suppress-next-line PhanTypeArraySuspiciousNullable $params always set here
		$primary = $this->params['primary'];
		if ( ( $primary == 'primary' && !$coord->primary )
			|| ( $primary == 'secondary' && $coord->primary )
		) {
			return false;
		}
		return true;
	}

	/**
	 * Adds debug information to API result
	 */
	private function addDebugInfo( \Elastica\ResultSet $resultSet, \Elastica\Query $query ): void {
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
