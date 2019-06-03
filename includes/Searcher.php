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
		/** @phan-suppress-next-line PhanTypeMismatchArgument */
		$connection = new \CirrusSearch\Connection( $config );

		parent::__construct( $connection, $user, 0 );
	}

	/**
	 * Perform search
	 *
	 * @param \Elastica\Query $query
	 * @param int[] $namespaces Namespaces used
	 * @param string $queryType Query description for logging
	 * @return \Elastica\ResultSet
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
			$this->failure( $ex );
			throw $ex;
		}

		return $result;
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
