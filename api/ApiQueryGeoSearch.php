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
	 * @param ApiPageSet $resultPageSet
	 */
	private function run( $resultPageSet = null ) {
		$params = $this->extractRequestParams();
		$exclude = false;

		$this->requireOnlyOneParameter( $params, 'coord', 'page' );
		if ( isset( $params['coord'] ) ) {
			$arr = explode( '|', $params['coord'] );
			if ( count( $arr ) != 2 || !GeoData::validateCoord( $arr[0], $arr[1], $params['globe'] ) ) {
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
		$this->addSpatialSearch( $lat, $lon, $radius );

		$this->addTables( array( 'page', 'geo_tags' ) );
		$this->addFields( array( 'gt_lat', 'gt_lon', 'gt_primary' ) );
		// retrieve some fields only if page set needs them
		if ( is_null( $resultPageSet ) ) {
			$this->addFields( array( 'page_id', 'page_namespace', 'page_title' ) );
		} else {
			$this->addFields( WikiPage::selectFields() );
		}
		foreach( $params['prop'] as $prop ) {
			if ( isset( Coord::$fieldMapping[$prop] ) ) {
				$this->addFields( Coord::$fieldMapping[$prop] );
			}
		}
		$this->addWhereFld( 'gt_globe', $params['globe'] );
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

		// Use information from PageImages
		//if ( defined( 'PAGE_IMAGES_INSTALLED' ) && $params['withoutphotos'] ) {
		//	$this->addTables( 'page_props' );
		//	$this->addJoinConds( array( 'page_props' => array( 'LEFT JOIN',
		//		"gt_page_id=pp_page AND pp_propname='has_photos'" )
		//	) );
		//	$this->addWhere( 'pp_page IS NULL' );
		//}

		$limit = $params['limit'];

		$res = $this->select( __METHOD__ );

		$rows = array();
		foreach ( $res as $row ) {
			$row->dist = GeoMath::distance( $lat, $lon, $row->gt_lat, $row->gt_lon );
			$rows[] = $row;
		}
		// sort in PHP because sorting via SQL involves a filesort
		usort( $rows, 'ApiQueryGeoSearch::compareRows' );
		$result = $this->getResult();
		foreach ( $rows as $row ) {
			if ( !$limit-- ) {
				break;
			}
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
					if ( isset( Coord::$fieldMapping[$prop] ) && isset( $row->{Coord::$fieldMapping[$prop]} ) ) {
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
		if ( is_null( $resultPageSet ) ) {
			$result->setIndexedTagName_internal(
				 array( 'query', $this->getModuleName() ), $this->getModulePrefix() );
		}
	}

	private function addSpatialSearch( $lat, $lon, $radius ) {
		global $wgGeoDataUseSphinx;

		if ( $wgGeoDataUseSphinx ) {
			$this->sphinxSearch( $lat, $lon, $radius );
		} else {
			$this->dbSearch( $lat, $lon, $radius );
		}
	}

	private function sphinxSearch( $lat, $lon, $radius ) {
		global $wgGeoDataSphinxHost, $wgGeoDataSphinxPort, $wgGeoDataSphinxIndex;
		$search = new SphinxClient();
		$search->SetServer( $wgGeoDataSphinxHost, $wgGeoDataSphinxPort );
		$search->SetMatchMode( SPH_MATCH_BOOLEAN );
		$search->SetArrayResult( true );
		$search->SetLimits( 0, 1000 );
		$search->SetGeoAnchor( 'lat', 'lon', deg2rad( $lat ), deg2rad( $lon ) );

		$search->SetFilterFloatRange( '@geodist', 0.0, floatval( $radius ) );
		$search->SetSortMode( SPH_SORT_ATTR_ASC, '@geodist' );

		// Build a tiled query that uses full-text index to improve search performance
		// equivalent to ( <lat1> || <lat2> || ... ) && ( <lon1> || <lon2> || ... )
		$rect = GeoMath::rectAround( $lat, $lon, $radius );
		$vals = array();
		foreach ( self::intRange( $rect["minLat"], $rect["maxLat"], 10 ) as $latInt ) {
			$vals[] = '"LAT' . round( $latInt ) . '"';
		}
		$query = implode( ' | ', $vals );
		$vals = array();
		foreach ( self::intRange( $rect["minLon"], $rect["maxLon"], 10 ) as $lonInt ) {
			$vals[] = '"LON' . round( $lonInt ) . '"';
		}
		$query .= ' ' . implode( ' | ', $vals );

		$result = $search->Query( $query, $wgGeoDataSphinxIndex );
		$err = $search->GetLastError();
		if ( $err ) {
			throw new MWException( "SphinxSearch error: $err" );
		}
		$warning = $search->GetLastWarning();
		if ( $warning ) {
			$this->setWarning( "SphinxSearch warning: $warning" );
		}
		if ( !is_array( $result ) || !isset( $result['matches'] ) ) {
			throw new MWException( 'SphinxClient::Query() returned unexpected result' );
		}
		$ids = array();
		foreach ( $result['matches'] as $match ) {
			$ids[] = $match['id'];
		}
		$this->addWhere( array( 'gt_id' => $ids ) );
	}

	private function dbSearch( $lat, $lon, $radius ) {
		$rect = GeoMath::rectAround( $lat, $lon, $radius );
		$this->addWhereFld( 'gt_lat_int', self::intRange( $rect["minLat"], $rect["maxLat"] ) );
		$this->addWhereFld( 'gt_lon_int', self::intRange( $rect["minLon"], $rect["maxLon"] ) );

		$this->addWhereRange( 'gt_lat', 'newer', $rect["minLat"], $rect["maxLat"], false );
		if ( $rect["minLon"] > $rect["maxLon"] ) {
			$this->addWhere( "gt_lon < {$rect['maxLon']} OR gt_lon > {$rect['minLon']}" );
		} else {
			$this->addWhereRange( 'gt_lon', 'newer', $rect["minLon"], $rect["maxLon"], false );
		}
		$this->addOption( 'USE INDEX', array( 'geo_tags' => 'gt_spatial' ) );
	}

	private static function compareRows( $row1, $row2 ) {
		if ( $row1->dist < $row2->dist ) {
			return -1;
		} elseif ( $row1->dist > $row2->dist ) {
			return 1;
		}
		return 0;
	}

	/**
	 * Returns a range of tenths of degree
	 *
	 * @param float $start
	 * @param float $end
	 * @param int|null $granularity
	 *
	 * @return Array
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

	public function getAllowedParams() {
		global $wgMaxGeoSearchRadius, $wgDefaultGlobe;
		$params = array (
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
			// @todo: globe selection disabled until we have a real use case
			'globe' => array(
				ApiBase::PARAM_TYPE => (array)$wgDefaultGlobe,
				ApiBase::PARAM_DFLT => $wgDefaultGlobe,
			),
			'namespace' => array(
				ApiBase::PARAM_TYPE => 'namespace',
				ApiBase::PARAM_DFLT => NS_MAIN,
				ApiBase::PARAM_ISMULTI => true,
			),
			'prop' => array(
				ApiBase::PARAM_TYPE => array( 'type', 'name', 'dim', 'country', 'region' ),
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_ISMULTI => true,
			),
			'primary' => array(
				ApiBase::PARAM_TYPE => array( 'yes', 'no' ),
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_DFLT => 'yes',
			),
		);
		if ( defined( 'PAGE_IMAGES_INSTALLED' ) ) {
			$params['withoutphotos'] = false;
		}
		return $params;
	}

	public function getParamDescription() {
		global $wgDefaultGlobe;
		$params = array(
			'coord' => 'Coordinate around which to search: two floating-point values separated by pipe (|)',
			'page' => 'Title of page around which to search',
			'radius' => 'Search radius in meters',
			'maxdim' => 'Restrict search to objects no larger than this, in meters',
			'limit' => 'Maximum number of pages to return',
			'globe' => "Globe to search on (by default ``{$wgDefaultGlobe}'')",
			'namespace' => 'Namespace(s) to search',
			'prop' => 'What additional coordinate properties to return',
			'primary' => "Whether to return only primary coordinates (``yes''), secondary (``no'') or both (``yes|no'')"
		);
		if ( defined( 'PAGE_IMAGES_INSTALLED' ) ) {
			$params['withoutphotos'] = 'Return only pages without photos';
		}
		return $params;
	}

	public function getDescription() {
		return 'Returns pages around the given point';
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => '_invalid-page', 'info' => "Invalid page title provided" ),
			array( 'code' => '_nonexistent-page', 'info' => "Page does not exist" ),
			array( 'code' => '_no-coordinates', 'info' => 'Page coordinates unknown' ),
		) );
	}

	public function getExamples() {
		return array(
			"api.php?action=query&list=geosearch&gsradius=10000&gscoord=37.786971|-122.399677" => 
				"Search around the point with coordinates 37° 47′ 13.1″ N, 122° 23′ 58.84″ W",
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:GeoData#list.3Dgeosearch';
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
}
