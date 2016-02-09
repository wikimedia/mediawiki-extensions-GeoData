<?php

namespace GeoData;

use Article;
use Content;
use DatabaseUpdater;
use LinksUpdate;
use LocalFile;
use MWException;
use OutputPage;
use Parser;
use ParserOptions;
use ParserOutput;
use Title;
use User;
use WikiPage;

class Hooks {
	/**
	 * LoadExtensionSchemaUpdates hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @param DatabaseUpdater $updater
	 * @throws MWException
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
				$updater->addExtensionUpdate( array( 'GeoData\Hooks::upgradeToDecimal' ) );
				break;
			default:
				throw new MWException( 'GeoData extension currently supports only MySQL and SQLite' );
		}
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
	 * @param array $files
	 */
	public static function onUnitTestsList( &$files ) {
		$files[] = dirname( __DIR__ ) . '/tests';
	}

	/**
	 * ParserFirstCallInit hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( &$parser ) {
		$parser->setFunctionHook( 'coordinates',
			array( new CoordinatesParserFunction( $parser ), 'coordinates' ),
			Parser::SFH_OBJECT_ARGS
		);
	}

	/**
	 * ArticleDeleteComplete hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 *
	 * @param Article $article
	 * @param User $user
	 * @param string $reason
	 * @param int $id
	 */
	public static function onArticleDeleteComplete( &$article, User &$user, $reason, $id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'geo_tags', array( 'gt_page_id' => $id ), __METHOD__ );
	}

	/**
	 * LinksUpdate hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdate
	 *
	 * @param LinksUpdate $linksUpdate
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

		\MediaWiki\suppressWarnings();
		$metadata = unserialize( $metadata );
		\MediaWiki\restoreWarnings();

		if ( isset( $metadata ) && isset( $metadata['GPSLatitude'] ) && isset( $metadata['GPSLongitude'] ) ) {
			$lat = $metadata['GPSLatitude'];
			$lon = $metadata['GPSLongitude'];
			if ( GeoData::validateCoord( $lat, $lon, 'earth' ) ) {
				$coord = new Coord( $lat, $lon );
				$coord->primary = true;
				return $coord;
			}
		}
		return null;
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
	}

	/**
	 * OutputPageParserOutput hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageParserOutput
	 *
	 * @param OutputPage $out
	 * @param ParserOutput $po
	 */
	public static function onOutputPageParserOutput( OutputPage &$out, ParserOutput $po ) {
		global $wgGeoDataInJS;

		if ( $wgGeoDataInJS && isset( $po->geoData ) ) {
			$coord = $po->geoData->getPrimary();
			if ( !$coord ) {
				return;
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
	}

	/**
	 * CirrusSearchMappingConfig hook handler
	 * Adds our stuff to CirrusSearch/Elasticsearch schema
	 *
	 * @param array $config
	 */
	public static function onCirrusSearchMappingConfig( array &$config ) {
		global $wgGeoDataUseCirrusSearch, $wgGeoDataBackend, $wgGeoDataCoordinatesCompression;
		if ( !$wgGeoDataUseCirrusSearch && $wgGeoDataBackend != 'elastic' ) {
			return;
		}
		$pageConfig = $config['page'];

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
		$config['page'] = $pageConfig;
	}

	/**
	 * CirrusSearchBuildDocumentParse hook handler
	 *
	 * @param \Elastica\Document $doc
	 * @param Title $title
	 * @param Content $content
	 * @param ParserOutput $parserOutput
	 */
	public static function onCirrusSearchBuildDocumentParse( \Elastica\Document $doc,
		Title $title,
		Content $content,
		ParserOutput $parserOutput )
	{
		global $wgGeoDataUseCirrusSearch, $wgGeoDataBackend;
		if ( !( $wgGeoDataUseCirrusSearch || $wgGeoDataBackend == 'elastic' )
			|| !isset( $parserOutput->geoData ) )
		{
			return;
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
	}

	/**
	 * Add to the tables cloned for parser testing
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserTestTables
	 *
	 * @param array $tables The tables to duplicate structure of
	 */
	public static function onParserTestTables( &$tables ) {
		$tables[] = 'geo_tags';
	}
}
