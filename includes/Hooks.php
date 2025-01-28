<?php

namespace GeoData;

use CirrusSearch\CirrusSearch;
use GeoData\Search\CoordinatesIndexField;
use MediaWiki\Config\Config;
use MediaWiki\Content\Content;
use MediaWiki\Content\ContentHandler;
use MediaWiki\Content\Hook\SearchDataForIndexHook;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use MediaWiki\FileRepo\File\File;
use MediaWiki\FileRepo\RepoGroup;
use MediaWiki\Hook\FileUploadHook;
use MediaWiki\Hook\LinksUpdateCompleteHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Logging\ManualLogEntry;
use MediaWiki\MainConfigNames;
use MediaWiki\Output\Hook\OutputPageParserOutputHook;
use MediaWiki\Page\Hook\ArticleDeleteCompleteHook;
use MediaWiki\Page\WikiPage;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\User;
use SearchEngine;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\LBFactory;

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
	private IConnectionProvider $connectionProvider;
	private LBFactory $lbFactory;
	private RepoGroup $repoGroup;
	private WikiPageFactory $wikiPageFactory;

	public function __construct(
		Config $config,
		IConnectionProvider $connectionProvider,
		LBFactory $lbFactory,
		RepoGroup $repoGroup,
		WikiPageFactory $wikiPageFactory
	) {
		$this->config = $config;
		$this->connectionProvider = $connectionProvider;
		$this->lbFactory = $lbFactory;
		$this->repoGroup = $repoGroup;
		$this->wikiPageFactory = $wikiPageFactory;
	}

	/**
	 * ParserFirstCallInit hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 *
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook( 'coordinates',
			[ new CoordinatesParserFunction( $this->config ), 'coordinates' ],
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
		$dbw = $this->connectionProvider->getPrimaryDatabase();
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
		$coordFromMetadata = $this->getCoordinatesIfFile( $linksUpdate->getTitle() );
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
		$this->doLinksUpdate( $data, $linksUpdate->getPageId(), $ticket );
	}

	private function getCoordinatesIfFile( LinkTarget $title ): ?Coord {
		if ( !$title->inNamespace( NS_FILE ) ) {
			return null;
		}
		$file = $this->repoGroup->getLocalRepo()
			->findFile( $title, [ 'ignoreRedirect' => true ] );
		if ( !$file ) {
			return null;
		}
		$metadata = $file->getMetadataItems( [ 'GPSLatitude', 'GPSLongitude' ] );
		if ( isset( $metadata['GPSLatitude'] ) && isset( $metadata['GPSLongitude'] ) ) {
			$lat = $metadata['GPSLatitude'];
			$lon = $metadata['GPSLongitude'];
			$globe = new Globe( Globe::EARTH );
			// T165800: Skip files with meaningless 0, 0 coordinates
			if ( ( $lat || $lon ) &&
				$globe->coordinatesAreValid( $lat, $lon )
			) {
				$coord = new Coord( $lat, $lon, $globe );
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
	private function doLinksUpdate( array $coords, $pageId, $ticket ) {
		$indexGranularity = $this->config->get( 'GeoDataBackend' ) === 'db' ?
			$this->config->get( 'GeoDataIndexGranularity' ) : null;

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

		$dbw = $this->connectionProvider->getPrimaryDatabase();
		$ticket = $ticket ?: $this->lbFactory->getEmptyTransactionTicket( __METHOD__ );
		$batchSize = $this->config->get( MainConfigNames::UpdateRowsPerQuery );

		$deleteIds = array_keys( $delete );
		foreach ( array_chunk( $deleteIds, $batchSize ) as $deleteIdBatch ) {
			$dbw->newDeleteQueryBuilder()
				->deleteFrom( 'geo_tags' )
				->where( [ 'gt_id' => $deleteIdBatch ] )
				->caller( __METHOD__ )
				->execute();
			$this->lbFactory->commitAndWaitForReplication( __METHOD__, $ticket );
		}

		foreach ( array_chunk( $add, $batchSize ) as $addBatch ) {
			$dbw->newInsertQueryBuilder()
				->insertInto( 'geo_tags' )
				->rows( $addBatch )
				->caller( __METHOD__ )
				->execute();
			$this->lbFactory->commitAndWaitForReplication( __METHOD__, $ticket );
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
		$wp = $this->wikiPageFactory->newFromTitle( $file->getTitle() );
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
				if ( !$coord->sameGlobe( Globe::EARTH ) ) {
					continue;
				}
				if ( !$coord->isValid() ) {
					wfDebugLog( 'CirrusSearchChangeFailed',
						"Invalid coordinates [{$coord->lat}, {$coord->lon}] on page "
							. $page->getTitle()->getPrefixedText()
					);
					continue;
				}
				$coords[] = $this->coordToElastic( $coord );
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
	private function coordToElastic( Coord $coord ) {
		$result = $coord->getAsArray();
		$result['coord'] = [ 'lat' => $coord->lat, 'lon' => $coord->lon ];
		unset( $result['id'] );
		unset( $result['lat'] );
		unset( $result['lon'] );

		return $result;
	}

}
