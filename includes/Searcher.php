<?php

namespace GeoData;


use CirrusSearch\ElasticsearchIntermediary;
use CirrusSearch\SearchConfig;
use CirrusSearch\SearchRequestLog;
use ConfigFactory;
use Elastica\Exception\ExceptionInterface;
use User;

/**
 * Performs ES searches via CirrusSearch infrastructure
 */
class Searcher extends ElasticsearchIntermediary {
	public function __construct( User $user = null ) {
		/** @var SearchConfig $config */
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'CirrusSearch' );
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

		try {
			$log = $this->newLog( 'performing {queryType}', $queryType );
			$this->start( $log );
			$result = $search->search();
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
	 * @return SearchRequestLog
	 */
	protected function newLog( $description, $queryType, array $extra = [] ) {
		return new SearchRequestLog(
			$this->connection->getClient(),
			$description,
			$queryType,
			$extra
		);
	}
}
