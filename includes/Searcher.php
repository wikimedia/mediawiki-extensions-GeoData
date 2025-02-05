<?php

namespace GeoData;

use CirrusSearch\Connection;
use CirrusSearch\ElasticsearchIntermediary;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\SearchConfig;
use CirrusSearch\SearchRequestLog;
use CirrusSearch\Util;
use Elastica\Exception\ExceptionInterface;
use Elastica\Exception\ResponseException;
use Elastica\Query;
use Elastica\Search;
use MediaWiki\Config\ConfigException;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;
use MediaWiki\WikiMap\WikiMap;
use StatusValue;

/**
 * Performs ES searches via CirrusSearch infrastructure
 */
class Searcher extends ElasticsearchIntermediary {

	private SearchConfig $config;

	/**
	 * @param UserIdentity|null $user
	 * @throws ConfigException
	 */
	public function __construct( ?UserIdentity $user = null ) {
		/** @var SearchConfig $config */
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'CirrusSearch' );
		'@phan-var SearchConfig $config';
		$this->config = $config;
		$connection = new Connection( $config );

		parent::__construct( $connection, $user, 0 );
	}

	/**
	 * Perform search
	 *
	 * @param Query $query
	 * @param int[] $namespaces Namespaces used
	 * @param string $queryType Query description for logging
	 * @return StatusValue Holds a \Elastica\ResultSet
	 * @throws ExceptionInterface
	 */
	public function performSearch( Query $query, array $namespaces, string $queryType ): StatusValue {
		$indexSuffix = $this->connection->pickIndexSuffixForNamespaces( $namespaces );
		$index = $this->connection->getIndex( WikiMap::getCurrentWikiId(), $indexSuffix );
		$search = $index->createSearch( $query );

		$this->connection->setTimeout( $this->getClientTimeout( $queryType ) );
		$search->setOption( Search::OPTION_TIMEOUT, $this->getTimeout( $queryType ) );

		$work = function () use ( $search, $queryType, $namespaces ) {
			try {
				$log = $this->newLog( 'performing {queryType}', $queryType, [], $namespaces );
				$this->start( $log );
				$result = $search->search();
				if ( !$result->getResponse()->isOk() ) {
					$req = $this->connection->getClient()->getLastRequest();
					// Not really the right exception, this is probably a status code problem.
					// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
					throw new ResponseException( $req, $result->getResponse() );
				}
				$this->success();
				$status = StatusValue::newGood( $result );
				if ( $result->getResponse()->getData()['timed_out'] ?? false ) {
					// only partial results returned
					$status->warning( 'geodata-search-timeout' );
				}
				return $status;
			} catch ( ExceptionInterface $ex ) {
				return $this->failure( $ex );
			}
		};
		return Util::doPoolCounterWork( $queryType, $this->user, $work );
	}

	/**
	 * @param string $description
	 * @param string $queryType
	 * @param string[] $extra
	 * @param array|null $namespaces
	 * @return SearchRequestLog
	 */
	protected function newLog(
		$description,
		$queryType,
		array $extra = [],
		?array $namespaces = null
	): SearchRequestLog {
		return new SearchRequestLog(
			$this->connection->getClient(),
			$description,
			$queryType,
			$extra,
			$namespaces
		);
	}

	/**
	 * @param int[] $namespaces
	 * @return array[] Rescore configurations as used by elasticsearch.
	 */
	public function getRelevanceRescoreConfigurations( array $namespaces ): array {
		$searchContext = new SearchContext( $this->config, $namespaces );
		return $searchContext->getRescore();
	}
}
