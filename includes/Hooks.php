<?php

namespace GeoData;

use ApiQuery;
use CirrusSearch\CirrusSearch;
use Content;
use ContentHandler;
use File;
use GeoData\Api\QueryGeoSearch;
use GeoData\Api\QueryGeoSearchDb;
use GeoData\Api\QueryGeoSearchElastic;
use GeoData\Search\CoordinatesIndexField;
use ManualLogEntry;
use MediaWiki\Config\Config;
use MediaWiki\Content\Hook\SearchDataForIndexHook;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use MediaWiki\Hook\FileUploadHook;
use MediaWiki\Hook\LinksUpdateCompleteHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\Hook\OutputPageParserOutputHook;
use MediaWiki\Page\Hook\ArticleDeleteCompleteHook;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\User;
use SearchEngine;
use WikiPage;

/**
 * Hook handlers
 * @todo: tests
 */
class Hooks implements
	SearchDataForIndexHook,
	OutputPageParserOutputHook,
	ParserFirstCallInitHook,
	ArticleDeleteCompleteHook,
	LinksUpdateCompleteHook,
	FileUploadHook
{

	private Config $config;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * ParserFirstCallInit hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 *
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook( 'coordinates',
			[ new CoordinatesParserFunction(), 'coordinates' ],
			Parser::SFH_OBJECT_ARGS
		);
	}

	/**
	 * ArticleDeleteComplete hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 *
	 * @param WikiPage $article
	 * @param User $user
	 * @param string $reason
	 * @param int $id
	 * @param Content|null $content
	 * @param ManualLogEntry $logEntry
	 * @param int $archivedRevisionCount
	 */
	public function onArticleDeleteComplete( $article, $user, $reason, $id,
		$content, $logEntry, $archivedRevisionCount
	) {
		$dbw = MediaWikiServices::getInstance()->getConnectionProvider()->getPrimaryDatabase();
		$dbw->newDeleteQueryBuilder()
			->deleteFrom( 'geo_tags' )
			->where( [ 'gt_page_id' => $id ] )
			->caller( __METHOD__ )
			->execute();
	}

	/**
	 * LinksUpdateComplete hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateComplete
	 *
	 * @param LinksUpdate $linksUpdate
	 * @param int|null $ticket
	 */
	public function onLinksUpdateComplete( $linksUpdate, $ticket ) {
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

	private static function getCoordinatesIfFile( LinkTarget $title ): ?Coord {
		if ( !$title->inNamespace( NS_FILE ) ) {
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
			$globe = new Globe();
			// T165800: Skip files with meaningless 0, 0 coordinates
			if ( ( $lat || $lon ) &&
				$globe->coordinatesAreValid( $lat, $lon )
			) {
				$coord = new Coord( $lat, $lon, $globe->getName() );
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
		$config = $services->getMainConfig();
		$indexGranularity = $config->get( 'GeoDataBackend' ) === 'db' ?
			$config->get( 'GeoDataIndexGranularity' ) : null;

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
				$add[] = $new->getRow( $pageId, $indexGranularity );
			}
		}

		$dbw = $services->getConnectionProvider()->getPrimaryDatabase();
		$lbFactory = $services->getDBLoadBalancerFactory();
		$ticket = $ticket ?: $lbFactory->getEmptyTransactionTicket( __METHOD__ );
		$batchSize = $config->get( MainConfigNames::UpdateRowsPerQuery );

		$deleteIds = array_keys( $delete );
		foreach ( array_chunk( $deleteIds, $batchSize ) as $deleteIdBatch ) {
			$dbw->newDeleteQueryBuilder()
				->deleteFrom( 'geo_tags' )
				->where( [ 'gt_id' => $deleteIdBatch ] )
				->caller( __METHOD__ )
				->execute();
			$lbFactory->commitAndWaitForReplication( __METHOD__, $ticket );
		}

		foreach ( array_chunk( $add, $batchSize ) as $addBatch ) {
			$dbw->newInsertQueryBuilder()
				->insertInto( 'geo_tags' )
				->rows( $addBatch )
				->caller( __METHOD__ )
				->execute();
			$lbFactory->commitAndWaitForReplication( __METHOD__, $ticket );
		}
	}

	/**
	 * FileUpload hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
	 *
	 * @param File $file
	 * @param bool $reupload
	 * @param bool $hasDescription
	 */
	public function onFileUpload( $file, $reupload, $hasDescription ) {
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
			DeferredUpdates::addCallableUpdate( function () use ( $lu ) {
				$this->onLinksUpdateComplete( $lu, null );
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
				if ( $coord->globe !== Globe::EARTH ) {
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
