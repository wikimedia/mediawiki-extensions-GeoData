<?php

namespace GeoData;

use ApiQuery;
use Article;
use CirrusSearch\CirrusSearch;
use CirrusSearch\SearchConfig;
use Config;
use ContentHandler;
use DatabaseUpdater;
use GeoData\Api\QueryGeoSearch;
use GeoData\Api\QueryGeoSearchDb;
use GeoData\Api\QueryGeoSearchElastic;
use GeoData\Search\CirrusNearCoordBoostFeature;
use GeoData\Search\CirrusNearCoordFilterFeature;
use GeoData\Search\CirrusNearTitleBoostFeature;
use GeoData\Search\CirrusNearTitleFilterFeature;
use GeoData\Search\CoordinatesIndexField;
use LinksUpdate;
use LocalFile;
use MediaWiki\Content\Hook\SearchDataForIndexHook;
use MediaWiki\Hook\OutputPageParserOutputHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MWException;
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
class Hooks implements SearchDataForIndexHook, OutputPageParserOutputHook {

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * Construct this hook handler
	 *
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * LoadExtensionSchemaUpdates hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @param DatabaseUpdater $updater
	 * @throws MWException
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$base = __DIR__ . '/../sql';
		$dbType = $updater->getDB()->getType();
		$updater->addExtensionTable( 'geo_tags', "$base/$dbType/tables-generated.sql" );
		if ( $dbType !== 'postgres' ) {
			$updater->addExtensionField( 'geo_tags', 'gt_lon_int', "$base/patch-geo_tags-add-lat_int-lon_int.sql" );
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
		self::doLinksUpdate( $data, $linksUpdate->getPageId(), $ticket );
	}

	/**
	 * @param Title $title
	 * @return Coord|null
	 */
	private static function getCoordinatesIfFile( Title $title ) {
		if ( $title->getNamespace() != NS_FILE ) {
			return null;
		}
		$file = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo()
			->findFile( $title, [ 'ignoreRedirect' => true ] );
		if ( !$file ) {
			return null;
		}
		$metadata = $file->getMetadataItems( [ 'GPSLatitude', 'GPSLongitude' ] );
		if ( isset( $metadata['GPSLatitude'] ) && isset( $metadata['GPSLongitude'] ) ) {
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
		$wp = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $file->getTitle() );
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
	 * @inheritDoc
	 */
	public function onOutputPageParserOutput( $out, $po ): void {
		$geoDataInJS = $this->config->get( 'GeoDataInJS' );

		if ( $geoDataInJS && CoordinatesOutput::getFromParserOutput( $po ) ) {
			$coord = CoordinatesOutput::getFromParserOutput( $po )->getPrimary();
			if ( !$coord ) {
				return;
			}
			$result = [];
			foreach ( $geoDataInJS as $param ) {
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
	public function onSearchIndexFields( array &$fields, SearchEngine $engine ) {
		$useCirrus = $this->config->get( 'GeoDataUseCirrusSearch' );
		$backend = $this->config->get( 'GeoDataBackend' );
		if ( !$useCirrus && $backend !== 'elastic' ) {
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
	 * @inheritDoc
	 */
	public function onSearchDataForIndex(
		&$fields,
		$handler,
		$page,
		$output,
		$engine
	) {
		self::doSearchDataForIndex( $fields, $output, $page );
	}

	/**
	 * SearchDataForIndex hook handler
	 *
	 * @param array &$fields
	 * @param ContentHandler $handler
	 * @param WikiPage $page
	 * @param ParserOutput $output
	 * @param SearchEngine $engine
	 * @param RevisionRecord $revision
	 */
	public function onSearchDataForIndex2(
		array &$fields,
		ContentHandler $handler,
		WikiPage $page,
		ParserOutput $output,
		SearchEngine $engine,
		RevisionRecord $revision
	) {
		self::doSearchDataForIndex( $fields, $output, $page );
	}

	/**
	 * Attach coordinates to the index document
	 *
	 * @param array &$fields
	 * @param ParserOutput $parserOutput
	 * @param WikiPage $page
	 * @return void
	 */
	private function doSearchDataForIndex( array &$fields, ParserOutput $parserOutput, WikiPage $page ): void {
		$useCirrus = $this->config->get( 'GeoDataUseCirrusSearch' );
		$backend = $this->config->get( 'GeoDataBackend' );

		if ( ( $useCirrus || $backend == 'elastic' ) ) {
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

	/**
	 * @param ApiQuery $query
	 * @param string $moduleName
	 * @return QueryGeoSearch
	 */
	public static function createQueryGeoSearchBackend( ApiQuery $query, $moduleName ): QueryGeoSearch {
		$geoDataBackend = $query->getConfig()->get( 'GeoDataBackend' );

		switch ( strtolower( $geoDataBackend ) ) {
			case 'db':
				return new QueryGeoSearchDb( $query, $moduleName );
			case 'elastic':
				return new QueryGeoSearchElastic( $query, $moduleName );
			default:
				throw new \RuntimeException( 'GeoDataBackend data backend cannot be empty' );
		}
	}
}
