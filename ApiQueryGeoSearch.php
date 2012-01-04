<?php

class ApiQueryGeoSearch extends ApiQueryGeneratorBase {
	const MIN_RADIUS = 10;

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'gs' );
	}

	public function execute() {
		$this->run();
	}

	public function getCacheMode( $params ) {
		return 'public';
	}

	public function executeGenerator( $resultPageSet ) {
		$this->run( $resultPageSet );
	}

	/**
	 * @param $resultPageSet ApiPageSet
	 * @return
	 */
	private function run( $resultPageSet = null ) {
		global $wgMaxGeoSearchRadius;

		$params = $this->extractRequestParams();
		$exclude = false;

		$this->requireOnlyOneParameter( $params, 'coord', 'page' );
		if ( isset( $params['coord'] ) ) {
			$arr = explode( '|', $params['coord'] );
			if ( count( $arr ) != 2 || !GeoData::validateCoord( $arr[0], $arr[1] ) ) {
				$this->dieUsage( 'Invalid coordinate provided', '_invalid-coord' );
			}
			$lat = $arr[0];
			$lon = $arr[1];
		} elseif ( isset( $params['page'] ) ) {
			$t = Title::newFromText( $params['page'] );
			if ( !$t || !$t->canExist() ) {
				$this->dieUsage( "Invalid page title `{$params['page']}' provided", '_invalid-page' );
			}
			if ( !$t->exists() ) {
				$this->dieUsage( "Page `{$params['page']}' does not exist", '_nonexistent-page' );
			}
			$coord = GeoData::getPageCoordinates( $t );
			if ( !$coord ) {
				$this->dieUsage( 'Page coordinates unknown', '_no-coordinates' );
			}
			$lat = $coord->lat;
			$lon = $coord->lon;
			$exclude = $t->getArticleID();
		}
		$lat = floatval( $lat );
		$lon = floatval( $lon );
		$radius = intval( $params['radius'] );
		$rect = GeoMath::rectAround( $lat, $lon, $radius );

		$dbr = wfGetDB( DB_SLAVE );
		$this->addTables( array( 'geo_tags', 'page' ) );
		$this->addFields( array( 'page_namespace', 'page_title', 'gt_lat', 'gt_lon', 
			"{$dbr->tablePrefix()}gd_distance( {$lat}, {$lon}, gt_lat, gt_lon ) AS dist" )
		);
		$this->addWhereRange( 'gt_lat', 'newer', $rect["minLat"], $rect["maxLat"], false );
		$this->addWhereRange( 'gt_lon', 'newer', $rect["minLon"], $rect["maxLon"], false );
		//$this->addWhere( 'dist < ' . intval( $radius ) ); hasta be in HAVING, not WHERE
		$this->addWhere( 'gt_page_id = page_id' );
		if ( $exclude ) {
			$this->addWhere( "gt_page_id <> {$exclude}" );
		}
		if ( isset( $params['maxdim'] ) ) {
			$this->addWhere( 'gt_dim < ' . intval( $params['maxdim'] ) ); 
		}
		$this->addOption( 'ORDER BY', 'dist' );

		$limit = $params['limit'];
		$this->addOption( 'LIMIT', $limit );
		
		$res = $this->select( __METHOD__ );

		$count = 0;
		$result = $this->getResult();
		foreach ( $res as $row ) {
			if ( is_null( $resultPageSet ) ) {
				$title = Title::newFromRow( $row );
				$vals = array(
					'pageid' => intval( $row->page_id ),
					'ns' => intval( $title->getNamespace() ),
					'title' => $title->getPrefixedText(),
					'dist' => $row->dist,
				);
				$fit = $result->addValue( array( 'query', $this->getModuleName() ), null, $vals );
				if ( !$fit ) {
					break;
				}
			} else {
				$resultPageSet->processDbRow( $row );
			}
		}
	}

	public function getAllowedParams() {
		global $wgMaxGeoSearchRadius;
		return array (
			'coord' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'page' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'radius' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_MIN => self::MIN_RADIUS,
				ApiBase::PARAM_MAX => $wgMaxGeoSearchRadius,
				ApiBase::PARAM_RANGE_ENFORCE => true,
			),
			'maxdim' => array(
				ApiBase::PARAM_TYPE => 'integer',
			),
			'limit' => array(
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			),
		);
	}

	public function getParamDescription() {
		return array(
			'coord' => 'Coordinate around which to search: two floating-point values separated by pipe (|)',
			'page' => 'Page around which to search',
			'radius' => 'Search radius in meters',
			'maxdim' => 'Restrict search to onjects no larger than this, in meters',
			'limit' => 'Maximum number of pages to return',
		);
	}

	public function getDescription() {
		return 'Returns pages around the given point';
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
		) );//@todo:
	}

	public function getExamples() {
		global $wgVersion;
		if ( version_compare( $wgVersion, '1.19alpha', '>=' ) ) {
			return array(
				"api.php?action=query&list=geosearch&gsccord=37.786971|-122.399677" => 
					"Search around the point with coordinates 37° 47′ 13.1″ N, 122° 23′ 58.84″ W",
			);
		} else {
			return array(
				"Search around the point with coordinates 37° 47′ 13.1″ N, 122° 23′ 58.84″ W",
				"    api.php?action=query&list=geosearch&gsccord=37.786971|-122.399677"
			);
		}
	}

	public function getVersion() {
		return __CLASS__ . ': $Id: ApiQueryGeoSearch.php 106945 2011-12-21 15:00:59Z maxsem $';
	}
}
