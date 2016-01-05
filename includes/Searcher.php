<?php

namespace GeoData;


use CirrusSearch\ElasticsearchIntermediary;
use CirrusSearch\SearchConfig;
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
	 * @param string $queryType Query description for logging
	 * @return \Elastica\ResultSet
	 * @throws ExceptionInterface
	 */
	public function performSearch( \Elastica\Query $query, $queryType ) {
		$pageType = $this->connection->getPageType( wfWikiID() );
		$search = $pageType->createSearch( $query );

		try {
			$this->start( "performing $queryType", [ 'queryType' => $queryType ] );
			$result = $search->search();
			$this->success();
		} catch ( ExceptionInterface $ex ) {
			$this->failure( $ex );
			throw $ex;
		}

		return $result;
	}
}