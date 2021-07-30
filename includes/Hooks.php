<?php

namespace GeoData;

use ApiModuleManager;
use Article;
use CirrusSearch\CirrusSearch;
use CirrusSearch\SearchConfig;
use ContentHandler;
use DatabaseUpdater;
use GeoData\Api\QueryGeoSearch;
use GeoData\Search\CirrusNearCoordBoostFeature;
use GeoData\Search\CirrusNearCoordFilterFeature;
use GeoData\Search\CirrusNearTitleBoostFeature;
use GeoData\Search\CirrusNearTitleFilterFeature;
use GeoData\Search\CoordinatesIndexField;
use LinksUpdate;
use LocalFile;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MWException;
use OutputPage;
use Parser;
use ParserOutput;
use SearchEngine;
use Title;
use User;
use WikiPage;

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

		// T193855 - the extension registry doesn't load extension configuration before
		if ( !isset( $wgGeoDataBackend ) ) {
			$wgGeoDataBackend = 'db';
		}
		if ( $wgGeoDataBackend != 'db' && $wgGeoDataBackend != 'elastic' ) {
			throw new MWException( "Unrecognized backend '$wgGeoDataBackend'" );
		}
		switch ( $updater->getDB()->getType() ) {
			case 'sqlite':
			case 'mysql':
				$dir = __DIR__;

				if ( $wgGeoDataBackend != 'db' ) {
					$updater->addExtensionTable( 'geo_tags', "$dir/../sql/externally-backed.sql" );
					$updater->dropExtensionTable( 'geo_killlist',
						"$dir/../sql/drop-updates-killlist.sql" );
				} else {
					$updater->addExtensionTable( 'geo_tags', "$dir/../sql/db-backed.sql" );
				}
				$updater->addExtensionUpdate( [ 'GeoData\Hooks::upgradeToDecimal' ] );
				break;
			default:
				throw new MWException(
					'GeoData extension currently supports only MySQL and SQLite'
				);
		}
	}

	/**
	 * Database schema update hook
	 *
	 * @param DatabaseUpdater $updater
	 */
	public static function upgradeToDecimal( DatabaseUpdater $updater ) {
		$db = $updater->getDB();
		if ( $db->getType() !== 'mysql' ) {
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
	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'coordinates',
			[ new CoordinatesParserFunction(), 'coordinates' ],
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
	public static function onArticleDeleteComplete( $article, User $user, $reason, $id ) {
		$dbw = wfGetDB( DB_PRIMARY );
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
		$coordsOutput = CoordinatesOutput::getFromParserOutput( $out );
		if ( $coordsOutput ) {
			// Use coordinates from file metadata unless overridden on description page
			if ( $coordFromMetadata && !$coordsOutput->hasPrimary() ) {
				$coordsOutput->addPrimary( $coordFromMetadata );
			}
			$data = $coordsOutput->getAll();
		} elseif ( $coordFromMetadata ) {
			$data[] = $coordFromMetadata;
		}

		if ( !$linksUpdate->mId ) {
			$linksUpdate->mId = $linksUpdate->getTitle()->getArticleID( Title::READ_LATEST );
		}
		if ( !$linksUpdate->mId ) {
			// Probably due to concurrent deletion or renaming of the page
			$logger = LoggerFactory::getInstance( 'SecondaryDataUpdate' );
			$logger->notice(
				'LinksUpdate: The Title object yields no ID. Perhaps the page was deleted?',
				[
					'page_title' => $linksUpdate->getTitle()->getPrefixedDBkey(),
					'cause_action' => $linksUpdate->getCauseAction(),
					'cause_agent' => $linksUpdate->getCauseAgent()
				]
			);
			// nothing to do
			return;
		}
		self::doLinksUpdate( $data, $linksUpdate->mId, $ticket );
	}

	/**
	 * @param Title $title
	 * @return Coord|null
	 */
	private static function getCoordinatesIfFile( Title $title ) {
		if ( $title->getNamespace() != NS_FILE ) {
			return null;
		}
		$file = MediaWikiServices::getInstance()->getRepoGroup()->findFile( $title );
		if ( !$file ) {
			return null;
		}
		$metadata = $file->getMetadata();

		\Wikimedia\suppressWarnings();
		$metadata = unserialize( $metadata );
		\Wikimedia\restoreWarnings();

		if ( isset( $metadata ) && isset( $metadata['GPSLatitude'] )
			&& isset( $metadata['GPSLongitude'] )
		) {
			$lat = $metadata['GPSLatitude'];
			$lon = $metadata['GPSLongitude'];
			$globe = new Globe( 'earth' );
			if ( $globe->coordinatesAreValid( $lat, $lon )
				// https://phabricator.wikimedia.org/T165800
				&& ( $lat != 0 || $lon != 0 )
			) {
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
	 * @throws \Wikimedia\Rdbms\DBUnexpectedError
	 */
	private static function doLinksUpdate( array $coords, $pageId, $ticket ) {
		$services = MediaWikiServices::getInstance();

		$add = [];
		$delete = [];
		$primary = ( isset( $coords[0] ) && $coords[0]->primary ) ? $coords[0] : null;
		foreach ( GeoData::getAllCoordinates( $pageId, [], DB_PRIMARY ) as $old ) {
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

		$dbw = wfGetDB( DB_PRIMARY );
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
		$po = $wp->makeParserOptions( 'canonical' );
		$pout = $wp->getParserOutput( $po );
		if ( !$pout ) {
			wfDebugLog( 'mobile',
				__METHOD__ . "(): no parser output returned for file {$file->getName()}"
			);
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
	public static function onOutputPageParserOutput( OutputPage $out, ParserOutput $po ) {
		global $wgGeoDataInJS;

		if ( $wgGeoDataInJS && CoordinatesOutput::getFromParserOutput( $po ) ) {
			$coord = CoordinatesOutput::getFromParserOutput( $po )->getPrimary();
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
	 * @param array &$fields
	 * @param SearchEngine $engine
	 */
	public static function onSearchIndexFields( array &$fields, SearchEngine $engine ) {
		global $wgGeoDataUseCirrusSearch, $wgGeoDataBackend;
		if ( !$wgGeoDataUseCirrusSearch && $wgGeoDataBackend !== 'elastic' ) {
			return;
		}
		if ( $engine instanceof CirrusSearch ) {
			/**
			 * @var CirrusSearch $engine
			 */
			$fields['coordinates'] = CoordinatesIndexField::build(
				'coordinates', $engine->getConfig(), $engine );
		} else {
			// Unsupported SearchEngine or explicitly disabled by config
		}
	}

	/**
	 * SearchDataForIndex hook handler
	 *
	 * @param array[] &$fields
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
			$coordsOutput = CoordinatesOutput::getFromParserOutput( $parserOutput );
			$allCoords = $coordsOutput !== null ? $coordsOutput->getAll() : [];
			$coords = [];

			/** @var Coord $coord */
			foreach ( $allCoords as $coord ) {
				if ( $coord->globe !== 'earth' ) {
					continue;
				}
				if ( !$coord->isValid() ) {
					wfDebugLog( 'CirrusSearchChangeFailed',
						"Invalid coordinates [{$coord->lat}, {$coord->lon}] on page "
							. $page->getTitle()->getPrefixedText()
					);
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
				QueryGeoSearch::class . ucfirst( $wgGeoDataBackend )
			);
		}
	}

	/**
	 * Add geo-search feature to search syntax
	 * @param SearchConfig $config
	 * @param array &$features
	 */
	public static function onCirrusSearchAddQueryFeatures( SearchConfig $config, array &$features ) {
		$features[] = new CirrusNearTitleBoostFeature( $config );
		$features[] = new CirrusNearTitleFilterFeature( $config );
		$features[] = new CirrusNearCoordBoostFeature( $config );
		$features[] = new CirrusNearCoordFilterFeature( $config );
	}
}
