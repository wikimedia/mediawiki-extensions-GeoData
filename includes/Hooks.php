<?php

namespace GeoData;

use ApiModuleManager;
use Article;
use Content;
use DatabaseUpdater;
use LinksUpdate;
use LocalFile;
use MediaWiki\MediaWikiServices;
use MWException;
use OutputPage;
use Parser;
use ParserOptions;
use ParserOutput;
use Title;
use User;
use WikiPage;
use SearchEngine;
use ContentHandler;

/**
 * Hook handlers
 * @todo: tests
 */
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
				$updater->addExtensionUpdate( [ 'GeoData\Hooks::upgradeToDecimal' ] );
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
	 * ParserFirstCallInit hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( &$parser ) {
		$parser->setFunctionHook( 'coordinates',
			[ new CoordinatesParserFunction( $parser ), 'coordinates' ],
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
		$dbw->delete( 'geo_tags', [ 'gt_page_id' => $id ], __METHOD__ );
	}

	/**
	 * LinksUpdateComplete hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateComplete
	 *
	 * @param LinksUpdate $linksUpdate
	 * @param int|null $ticket
	 */
	public static function onLinksUpdateComplete( LinksUpdate $linksUpdate, $ticket = null ) {
		$out = $linksUpdate->getParserOutput();
		$data = [];
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

		self::doLinksUpdate( $data, $linksUpdate->mId, $ticket );
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
			$globe = new Globe( 'earth' );
			if ( $globe->coordinatesAreValid( $lat, $lon ) ) {
				$coord = new Coord( $lat, $lon );
				$coord->primary = true;
				return $coord;
			}
		}
		return null;
	}

	/**
	 * @param Coord[] $coords
	 * @param int $pageId
	 * @param int|null $ticket
	 * @throws \DBUnexpectedError
	 */
	private static function doLinksUpdate( array $coords, $pageId, $ticket ) {
		$services = MediaWikiServices::getInstance();

		$add = [];
		$delete = [];
		$primary = ( isset( $coords[0] ) && $coords[0]->primary ) ? $coords[0] : null;
		foreach ( GeoData::getAllCoordinates( $pageId, [], DB_MASTER ) as $old ) {
			$delete[$old->id] = $old;
		}
		foreach ( $coords as $new ) {
			if ( !$new->primary && $new->equalsTo( $primary ) ) {
				// Don't save secondary coordinates pointing to the same place as the primary one
				continue;
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

		$dbw = wfGetDB( DB_MASTER );
		$lbFactory = $services->getDBLoadBalancerFactory();
		$ticket = $ticket ?: $lbFactory->getEmptyTransactionTicket( __METHOD__ );
		$batchSize = $services->getMainConfig()->get( 'UpdateRowsPerQuery' );

		$deleteIds = array_keys( $delete );
		foreach ( array_chunk( $deleteIds, $batchSize ) as $deleteIdBatch ) {
			$dbw->delete( 'geo_tags', [ 'gt_id' => $deleteIdBatch ], __METHOD__ );
			$lbFactory->commitAndWaitForReplication( __METHOD__, $ticket );
		}

		foreach ( array_chunk( $add, $batchSize ) as $addBatch ) {
			$dbw->insert( 'geo_tags', $addBatch, __METHOD__ );
			$lbFactory->commitAndWaitForReplication( __METHOD__, $ticket );
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
			// Make sure this has outer transaction scope (though the hook fires
			// in a deferred AutoCommitUdpate update, so it should be safe anyway).
			$lu = new LinksUpdate( $file->getTitle(), $pout );
			\DeferredUpdates::addCallableUpdate( function () use ( $lu ) {
				self::onLinksUpdateComplete( $lu );
			} );
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
			$result = [];
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
	 * Search index fields hook handler
	 * Adds our stuff to CirrusSearch/Elasticsearch schema
	 *
	 * @param array $fields
	 * @param SearchEngine $engine
	 */
	public static function onSearchIndexFields( array &$fields, SearchEngine $engine ) {
		global $wgGeoDataUseCirrusSearch, $wgGeoDataBackend;
		if ( $engine instanceof \CirrusSearch
			&& ( $wgGeoDataUseCirrusSearch || $wgGeoDataBackend  == 'elastic' )
		) {
			/**
			 * @var \CirrusSearch $engine
			 */
			$fields['coordinates'] = CoordinatesIndexField::build( 'coordinates', $engine->getConfig(), $engine );
		} else {
			// Unsupported SearchEngine or explicitly disabled by config
		}
	}

	/**
	 * SearchDataForIndex hook handler
	 *
	 * @param array[] $fields
	 * @param ContentHandler $contentHandler
	 * @param WikiPage $page
	 * @param ParserOutput $parserOutput
	 * @param SearchEngine $searchEngine
	 */
	public static function onSearchDataForIndex(
		array &$fields,
		ContentHandler $contentHandler,
		WikiPage $page,
		ParserOutput $parserOutput,
		SearchEngine $searchEngine
	) {
		global $wgGeoDataUseCirrusSearch, $wgGeoDataBackend;

		if ( ( $wgGeoDataUseCirrusSearch || $wgGeoDataBackend == 'elastic' ) ) {
			$allCoords = isset( $parserOutput->geoData )
				? $parserOutput->geoData->getAll()
				: [];
			$coords = [];

			/** @var Coord $coord */
			foreach ( $allCoords as $coord ) {
				if ( $coord->globe !== 'earth' ) {
					continue;
				}
				$coords[] = self::coordToElastic( $coord );
			}
			$fields['coordinates'] = $coords;
		}
	}

	/**
	 * Transforms coordinates into an array for insertion onto Elasticsearch
	 *
	 * @param Coord $coord
	 * @return array
	 */
	public static function coordToElastic( Coord $coord ) {
		$result = $coord->getAsArray();
		$result['coord'] = [ 'lat' => $coord->lat, 'lon' => $coord->lon ];
		unset( $result['id'] );
		unset( $result['lat'] );
		unset( $result['lon'] );

		return $result;
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

	/**
	 * ApiQuery::moduleManager hook to conditionally register
	 * geosearch API module
	 *
	 * @param ApiModuleManager $moduleManager
	 */
	public static function onApiQueryModuleManager( ApiModuleManager $moduleManager ) {
		global $wgGeoDataBackend;
		if ( !$moduleManager->isDefined( 'geosearch', 'list' ) ) {
			$moduleManager->addModule(
				'geosearch',
				'list',
				'GeoData\ApiQueryGeoSearch' . ucfirst( $wgGeoDataBackend )
			);
		}
	}
}
