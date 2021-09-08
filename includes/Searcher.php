<?php

namespace GeoData;

use CirrusSearch\ElasticsearchIntermediary;
use CirrusSearch\SearchConfig;
use CirrusSearch\SearchRequestLog;
use ConfigException;
use Elastica\Exception\ExceptionInterface;
use Elastica\Exception\ResponseException;
use Elastica\Search;
use MediaWiki\MediaWikiServices;
use StatusValue;
use User;

/**
 * Performs ES searches via CirrusSearch infrastructure
 */
class Searcher extends ElasticsearchIntermediary {
	/**
	 * @param User|null $user
	 * @throws ConfigException
	 */
	public function __construct( User $user = null ) {
		/** @var SearchConfig $config */
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'CirrusSearch' );
		'@phan-var SearchConfig $config';
		$connection = new \CirrusSearch\Connection( $config );

		parent::__construct( $connection, $user, 0 );
	}

	/**
	 * Perform search
	 *
	 * @param \Elastica\Query $query
	 * @param int[] $namespaces Namespaces used
	 * @param string $queryType Query description for logging
	 * @return \StatusValue Holds a \Elastica\ResultSet
	 * @throws ExceptionInterface
	 */
	public function performSearch( \Elastica\Query $query, array $namespaces, $queryType ) {
		$indexType = $this->connection->pickIndexTypeForNamespaces( $namespaces );
		$pageType = $this->connection->getPageType( wfWikiID(), $indexType );
		$search = $pageType->createSearch( $query );

		$this->connection->setTimeout( $this->getClientTimeout( $queryType ) );
		$search->setOption( Search::OPTION_TIMEOUT, $this->getTimeout( $queryType ) );

		try {
			$log = $this->newLog( 'performing {queryType}', $queryType, [], $namespaces );
			$this->start( $log );
			$result = $search->search();
			if ( !$result->getResponse()->isOk() ) {
				$req = $this->connection->getClient()->getLastRequest();
				// Not really the right exception, this is probably a status code problem.
				throw new ResponseException( $req, $result->getResponse() );
			}
			$this->success();
		} catch ( ExceptionInterface $ex ) {
			return $this->failure( $ex );
		}

		$status = StatusValue::newGood( $result );
		if ( $result->getResponse()->getData()['timed_out'] ?? false ) {
			// only partial results returned
			$status->warning( 'geodata-search-timeout' );
		}
		return $status;
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
		array $namespaces = null
	) {
		return new SearchRequestLog(
			$this->connection->getClient(),
			$description,
			$queryType,
			$extra,
			$namespaces
		);
	}
}
