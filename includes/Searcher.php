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

		$log = new SearchRequestLog(
			$this->connection->getClient(),
			$this->user,
			'performing {queryType}',
			$queryType
		);
		try {
			$this->start( $log );
			$result = $search->search();
			$this->success();
		} catch ( ExceptionInterface $ex ) {
			$this->failure( $ex );
			throw $ex;
		}

		return $result;
	}
}
