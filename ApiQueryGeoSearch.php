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
				$this->dieUsage( "Invalid page title ``{$params['page']}'' provided", '_invalid-page' );
			}
			if ( !$t->exists() ) {
				$this->dieUsage( "Page ``{$params['page']}'' does not exist", '_nonexistent-page' );
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
		$this->addFields( array( 'gt_lat', 'gt_lon', 'gt_primary',
			"{$dbr->tablePrefix()}gd_distance( {$lat}, {$lon}, gt_lat, gt_lon ) AS dist" )
		);
		// retrieve some fields only if page set needs them
		if ( is_null( $resultPageSet ) ) {
			$this->addFields( array( 'page_id', 'page_namespace', 'page_title' ) );
		} else {
			$this->addFields( array( "{$dbr->tableName( 'page' )}.*" ) );
		}
		foreach( $params['prop'] as $prop ) {
			if ( isset( Coord::$fieldMapping[$prop] ) ) {
				$this->addFields( Coord::$fieldMapping[$prop] );
			}
		}
		$this->addWhereFld( 'gt_globe', $params['globe'] );
		$this->addWhereRange( 'gt_lat', 'newer', $rect["minLat"], $rect["maxLat"], false );
		$this->addWhereRange( 'gt_lon', 'newer', $rect["minLon"], $rect["maxLon"], false );
		//$this->addWhere( 'dist < ' . intval( $radius ) ); hasta be in HAVING, not WHERE
		$this->addWhereFld( 'page_namespace', $params['namespace'] );
		$this->addWhere( 'gt_page_id = page_id' );
		if ( $exclude ) {
			$this->addWhere( "gt_page_id <> {$exclude}" );
		}
		if ( isset( $params['maxdim'] ) ) {
			$this->addWhere( 'gt_dim < ' . intval( $params['maxdim'] ) ); 
		}
		$primary = array_flip( $params['primary'] );
		$this->addWhereIf( array( 'gt_primary' => 1 ), isset( $primary['yes'] ) && !isset( $primary['no'] )	);
		$this->addWhereIf( array( 'gt_primary' => 0 ), !isset( $primary['yes'] ) && isset( $primary['no'] )	);
		$this->addOption( 'ORDER BY', 'dist' );

		$limit = $params['limit'];
		$this->addOption( 'LIMIT', $limit );
		
		$res = $this->select( __METHOD__ );

		$result = $this->getResult();
		foreach ( $res as $row ) {
			if ( is_null( $resultPageSet ) ) {
				$title = Title::newFromRow( $row );
				$vals = array(
					'pageid' => intval( $row->page_id ),
					'ns' => intval( $title->getNamespace() ),
					'title' => $title->getPrefixedText(),
					'lat' => floatval( $row->gt_lat ),
					'lon' => floatval( $row->gt_lon ),
					'dist' => round( $row->dist, 1 ),
				);
				if ( $row->gt_primary ) {
					$vals['primary'] = '';
				}
				foreach( $params['prop'] as $prop ) {
					if ( isset( Coord::$fieldMapping[$prop] ) ) {
						$field = Coord::$fieldMapping[$prop];
						$vals[$prop] = $row->$field;
					}
				}
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
		global $wgMaxGeoSearchRadius, $wgDefaultGlobe;
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
			'globe' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_DFLT => $wgDefaultGlobe,
			),
			'namespace' => array(
				ApiBase::PARAM_TYPE => 'namespace',
				ApiBase::PARAM_DFLT => NS_MAIN,
				ApiBase::PARAM_ISMULTI => true,
			),
			'prop' => array(
				ApiBase::PARAM_TYPE => array( 'type', 'name', 'country', 'region' ),
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_ISMULTI => true,
			),
			'primary' => array(
				ApiBase::PARAM_TYPE => array( 'yes', 'no' ),
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_DFLT => 'yes',
			),
		);
	}

	public function getParamDescription() {
		global $wgDefaultGlobe;
		return array(
			'coord' => 'Coordinate around which to search: two floating-point values separated by pipe (|)',
			'page' => 'Page around which to search',
			'radius' => 'Search radius in meters',
			'maxdim' => 'Restrict search to objects no larger than this, in meters',
			'limit' => 'Maximum number of pages to return',
			'globe' => "Globe to search on (by default ``{$wgDefaultGlobe}'')",
			'namespace' => 'Namespace(s) to search',
			'prop' => 'What additional coordinate properties to return',
			'primary' => "Whether to return only primary coordinates (``yes''), secondary (``no'') or both (``yes|no'')",
		);
	}

	public function getDescription() {
		return 'Returns pages around the given point';
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => '_invalid-page', 'info' => "Invalid page title provided" ),
			array( 'code' => '_nonexistent-page', 'info' => "Page does not exist" ),
			array( 'code' => '_no-coordinates', 'info' => 'Page coordinates unknown' ),
		) );//@todo:
	}

	public function getExamples() {
		return array(
			"api.php?action=query&list=geosearch&gsccord=37.786971|-122.399677" => 
				"Search around the point with coordinates 37° 47′ 13.1″ N, 122° 23′ 58.84″ W",
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
}
