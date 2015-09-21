<?php

class GeoDataHooks {
	/**
	 * LoadExtensionSchemaUpdates hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @param DatabaseUpdater $updater
	 * @throws MWException
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		global $wgGeoDataBackend;

		if ( $wgGeoDataBackend != 'db' && $wgGeoDataBackend != 'elastic' ) {
			throw new MWException( "Unrecognized backend '$wgGeoDataBackend'" );
		}
		switch ( $updater->getDB()->getType() ) {
			case 'sqlite':
			case 'mysql':
				$dir = __DIR__;

				if ( $wgGeoDataBackend != 'db' ) {
					$updater->addExtensionTable( 'geo_tags', "$dir/../sql/externally-backed.sql" );
					$updater->dropExtensionTable( 'geo_killlist', "$dir/../sql/drop-updates-killlist.sql" );
				} else {
					$updater->addExtensionTable( 'geo_tags', "$dir/../sql/db-backed.sql" );
				}
				$updater->addExtensionUpdate( array( 'GeoDataHooks::upgradeToDecimal' ) );
				break;
			default:
				throw new MWException( 'GeoData extension currently supports only MySQL and SQLite' );
		}
		return true;
	}

	public static function upgradeToDecimal( DatabaseUpdater $updater ) {
		$db = $updater->getDB();
		if ( $db->getType() != 'mysql' ) {
			// FLOAT is the same thing as DOUBLE in SQLite
			return;
		}
		$field = $db->fieldInfo( 'geo_tags', 'gt_lat' );
		// Doesn't support the old API, oh well
		if ( $field->type() === MYSQLI_TYPE_FLOAT ) {
			$updater->output( "...upgrading geo_tags coordinates from FLOAT to DECIMAL.\n" );
			$db->sourceFile( __DIR__ . '/../sql/float-to-decimal.sql' );
		} else {
			$updater->output( "...coordinates are already DECIMAL in geo_tags.\n" );
		}
	}

	/**
	 * UnitTestsList hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @param Array $files
	 * @return bool
	 */
	public static function onUnitTestsList( &$files ) {
		$dir = __DIR__ . '/../tests';
		$files[] = "$dir/CoordTest.php";
		$files[] = "$dir/GeoDataMathTest.php";
		$files[] = "$dir/MiscGeoDataTest.php";
		$files[] = "$dir/ParseCoordTest.php";
		$files[] = "$dir/TagTest.php";
		return true;
	}

	/**
	 * ParserFirstCallInit hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 *
	 * @param Parser $parser
	 * @return bool
	 */
	public static function onParserFirstCallInit( &$parser ) {
		global $wgGeoDataDisableParserFunction;

		if ( !$wgGeoDataDisableParserFunction ) {
			$parser->setFunctionHook( 'coordinates',
				array( new CoordinatesParserFunction( $parser ), 'coordinates' ),
				Parser::SFH_OBJECT_ARGS
			);
		}
		return true;
	}

	/**
	 * ArticleDeleteComplete hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 *
	 * @param Article $article
	 * @param User $user
	 * @param String $reason
	 * @param int $id
	 * @return bool
	 */
	public static function onArticleDeleteComplete( &$article, User &$user, $reason, $id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'geo_tags', array( 'gt_page_id' => $id ), __METHOD__ );

		return true;
	}

	/**
	 * LinksUpdate hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdate
	 *
	 * @param LinksUpdate $linksUpdate
	 * @return bool
	 */
	public static function onLinksUpdate( &$linksUpdate ) {
		$out = $linksUpdate->getParserOutput();
		$data = array();
		$coordFromMetadata = self::getCoordinatesIfFile( $linksUpdate->getTitle() );
		if ( isset( $out->geoData ) ) {
			/** @var CoordinatesOutput $geoData */
			$geoData = $out->geoData;
			// Use coordinates from file metadata unless overridden on description page
			if ( $coordFromMetadata && !$geoData->getPrimary() ) {
				$geoData->addPrimary( $coordFromMetadata );
			}
			$data = $geoData->getAll();
		} elseif ( $coordFromMetadata ) {
			$data[] = $coordFromMetadata;
		}

		self::doLinksUpdate( $data, $linksUpdate->mId );

		return true;
	}

	private static function getCoordinatesIfFile( Title $title ) {
		if ( $title->getNamespace() != NS_FILE ) {
			return null;
		}
		$file = wfFindFile( $title );
		if ( !$file ) {
			return null;
		}
		$metadata = $file->getMetadata();
		wfSuppressWarnings();
		$metadata = unserialize( $metadata );
		wfRestoreWarnings();
		if ( isset( $metadata ) && isset( $metadata['GPSLatitude'] ) && isset( $metadata['GPSLongitude'] ) ) {
			$lat = $metadata['GPSLatitude'];
			$lon = $metadata['GPSLongitude'];
			$refs = self::decodeRefs( $metadata );
			$lat *= $refs[0];
			$lon *= $refs[1];
			if ( GeoData::validateCoord( $lat, $lon, 'earth' ) ) {
				$coord = new Coord( $lat, $lon );
				$coord->primary = true;
				return $coord;
			}
		}
		return null;
	}

	private static function decodeRefs( $metadata ) {
		global $wgGlobes;
		if ( isset( $metadata['GPSLatitudeRef'] ) && isset( $metadata['GPSLongitudeRef'] ) ) {
			$coordInfo = GeoData::getCoordInfo();
			$latRef = GeoData::parseSuffix( $metadata['GPSLatitudeRef'], $coordInfo['lat'] );
			$lonRef = GeoData::parseSuffix( $metadata['GPSLongitudeRef'], $wgGlobes['earth'] );
			if ( $latRef != 0 && $lonRef != 0 ) {
				return array( $latRef, $lonRef );
			}
		}
		return array( 1, 1 );
	}

	private static function doLinksUpdate( $coords, $pageId ) {
		global $wgGeoDataBackend;

		$dbw = wfGetDB( DB_MASTER );

		if ( $wgGeoDataBackend == 'db' && !count( $coords ) ) {
			$dbw->delete( 'geo_tags', array( 'gt_page_id' => $pageId ), __METHOD__ );
			return;
		}

		$prevCoords = GeoData::getAllCoordinates( $pageId, array(), DB_MASTER );
		$add = array();
		$delete = array();
		$primary = ( isset( $coords[0] ) && $coords[0]->primary ) ? $coords[0] : null;
		foreach ( $prevCoords as $old ) {
			$delete[$old->id] = $old;
		}
		/** @var Coord $new */
		foreach ( $coords as $new ) {
			if ( !$new->primary && $new->equalsTo( $primary ) ) {
				continue; // Don't save secondary coordinates pointing to the same place as the primary one
			}
			$match = false;
			foreach ( $delete as $id => $old ) {
				if ( $new->fullyEqualsTo( $old ) ) {
					unset( $delete[$id] );
					$match = true;
					break;
				}
			}
			if ( !$match ) {
				$add[] = $new->getRow( $pageId );
			}
		}

		if ( count( $delete ) ) {
			$deleteIds = array_keys( $delete );
			$dbw->delete( 'geo_tags', array( 'gt_id' => $deleteIds ), __METHOD__ );
		}
		if ( count( $add ) ) {
			$dbw->insert( 'geo_tags', $add, __METHOD__ );
		}
	}

	/**
	 * FileUpload hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
	 *
	 * @param LocalFile $file
	 * @return bool
	 */
	public static function onFileUpload( LocalFile $file ) {
		$wp = WikiPage::factory( $file->getTitle() );
		$po = new ParserOptions();
		$pout = $wp->getParserOutput( $po );
		if ( !$pout ) {
			wfDebugLog( 'mobile', __METHOD__ . "(): no parser output returned for file {$file->getName()}" );
		} else {
			$lu = new LinksUpdate( $file->getTitle(), $pout );
			self::onLinksUpdate( $lu );
		}
		return true;
	}

	/**
	 * OutputPageParserOutput hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageParserOutput
	 *
	 * @param OutputPage $out
	 * @param ParserOutput $po
	 *
	 * @return bool
	 */
	public static function onOutputPageParserOutput( OutputPage &$out, ParserOutput $po ) {
		global $wgGeoDataInJS;

		if ( $wgGeoDataInJS && isset( $po->geoData ) ) {
			$coord = $po->geoData->getPrimary();
			if ( !$coord ) {
				return true;
			}
			$result = array();
			foreach ( $wgGeoDataInJS as $param ) {
				if ( isset( $coord->$param ) ) {
					$result[$param] = $coord->$param;
				}
			}
			if ( $result ) {
				$out->addJsConfigVars( 'wgCoordinates', $result );
			}
		}

		return true;
	}

	/**
	 * CirrusSearchMappingConfig hook handler
	 * Adds our stuff to CirrusSearch/Elasticsearch schema
	 *
	 * @param array $config
	 *
	 * @return bool
	 */
	public static function onCirrusSearchMappingConfig( array &$config ) {
		global $wgGeoDataUseCirrusSearch, $wgGeoDataBackend, $wgGeoDataCoordinatesCompression;
		if ( !$wgGeoDataUseCirrusSearch && $wgGeoDataBackend != 'elastic' ) {
			return true;
		}
		if ( isset( $config[ 'page' ] ) ) {
			// Post CirrusSearch I2d92dc973a66180dc39c646254ce9b11ec269edc
			$pageConfig = $config[ 'page' ];
		} else {
			// Pre CirrusSearch I2d92dc973a66180dc39c646254ce9b11ec269edc
			$pageConfig = $config;
		}
		$pageConfig['properties']['coordinates'] = array(
			'type' => 'nested',
			'properties' => array(
				'coord' => array(
					'type' => 'geo_point',
					'lat_lon' => true,
				),
				'globe' => array( 'type' => 'string', 'index' => 'not_analyzed' ),
				'primary' => array( 'type' => 'boolean' ),
				'dim' => array( 'type' => 'float' ),
				'type' => array( 'type' => 'string', 'index' => 'not_analyzed' ),
				'name' => array( 'type' => 'string', 'index' => 'no' ),
				'country' => array( 'type' => 'string', 'index' => 'not_analyzed' ),
				'region' => array( 'type' => 'string', 'index' => 'not_analyzed' ),
			),
		);
		if ( $wgGeoDataCoordinatesCompression ) {
			$pageConfig['properties']['coordinates']['properties']['coord']['fielddata'] = array(
				'format' => 'compressed',
				'precision' => $wgGeoDataCoordinatesCompression,
			);
		}
		if ( isset( $config[ 'page' ] ) ) {
			// Post CirrusSearch I2d92dc973a66180dc39c646254ce9b11ec269edc
			$config[ 'page' ] = $pageConfig;
		} else {
			// Pre CirrusSearch I2d92dc973a66180dc39c646254ce9b11ec269edc
			$config = $pageConfig;
		}
		return true;
	}

	/**
	 * CirrusSearchBuildDocumentParse hook handler
	 *
	 * @param Elastica\Document $doc
	 * @param Title $title
	 * @param Content $content
	 * @param ParserOutput $parserOutput
	 * @return bool
	 */
	public static function onCirrusSearchBuildDocumentParse( Elastica\Document $doc,
		Title $title,
		Content $content,
		ParserOutput $parserOutput )
	{
		global $wgGeoDataUseCirrusSearch, $wgGeoDataBackend;
		if ( !( $wgGeoDataUseCirrusSearch || $wgGeoDataBackend == 'elastic' )
			|| !isset( $parserOutput->geoData ) )
		{
			return true;
		}

		$coords = array();
		/** @var Coord $coord */
		foreach ( $parserOutput->geoData->getAll() as $coord ) {
			$arr = $coord->getAsArray();
			$arr['coord'] = array( 'lat' => $coord->lat, 'lon' => $coord->lon );
			unset( $arr['id'] );
			unset( $arr['lat'] );
			unset( $arr['lon'] );
			$coords[] = $arr;
		}
		$doc->set( 'coordinates', $coords );
		return true;
	}

	/**
	 * Add to the tables cloned for parser testing
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserTestTables
	 *
	 * @param array $tables The tables to duplicate structure of
	 * @return bool
	 */
	public static function onParserTestTables( &$tables ) {
		$tables[] = 'geo_tags';
		return true;
	}
}
