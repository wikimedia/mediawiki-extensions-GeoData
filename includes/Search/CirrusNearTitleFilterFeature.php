<?php

namespace GeoData\Search;

use CirrusSearch\Parser\AST\KeywordFeatureNode;
use CirrusSearch\Query\Builder\QueryBuildingContext;
use CirrusSearch\Query\FilterQueryFeature;
use CirrusSearch\Query\SimpleKeywordFeature;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\SearchConfig;
use CirrusSearch\WarningCollector;
use Config;
use Elastica\Query\AbstractQuery;
use GeoData\Coord;

/**
 * Applies geo filtering to the query by providing a Title.
 *
 * Limits search results to a geographic area within the geographic area. All values
 * can be prefixed with a radius in m or km to apply. If not specified this defaults
 * to 5km.
 *
 * Examples:
 *  neartitle:Shanghai
 *  neartitle:50km,Seoul
 */
class CirrusNearTitleFilterFeature extends SimpleKeywordFeature implements FilterQueryFeature {
	use CirrusGeoFeature;

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * @return string[]
	 */
	protected function getKeywords() {
		return [ 'neartitle' ];
	}

	/**
	 * @param SearchContext $context
	 * @param string $key The keyword
	 * @param string $value The value attached to the keyword with quotes stripped
	 * @param string $quotedValue The original value in the search string, including quotes if used
	 * @param bool $negated Is the search negated? Not used to generate the returned AbstractQuery,
	 *  that will be negated as necessary. Used for any other building/context necessary.
	 * @return array Two element array, first an AbstractQuery or null to apply to the
	 *  query. Second a boolean indicating if the quotedValue should be kept in the search
	 *  string.
	 */
	protected function doApply( SearchContext $context, $key, $value, $quotedValue, $negated ) {
		list( $coord, $radius, $excludedPageId ) = $this->parseGeoNearbyTitle( $context, $key, $value );
		$filter = null;
		if ( $coord !== null ) {
			$filter = $this->doGetFilterQuery( $context->getConfig(), $coord, $radius, $excludedPageId );
		}
		return [ $filter, false ];
	}

	/**
	 * @param KeywordFeatureNode $node
	 * @param SearchConfig $config
	 * @param WarningCollector $warningCollector
	 * @return array
	 */
	public function expand(
		KeywordFeatureNode $node,
		SearchConfig $config,
		WarningCollector $warningCollector
	) {
		return $this->parseGeoNearbyTitle( $warningCollector, $node->getKey(), $node->getValue() );
	}

	/**
	 * Create a filter for near: and neartitle: queries.
	 *
	 * @param Coord $coord
	 * @param int $radius Search radius in meters
	 * @param string $docIdToExclude Document id to exclude, or "" for no exclusions.
	 * @return AbstractQuery
	 */
	public static function createQuery( Coord $coord, $radius, $docIdToExclude = '' ) {
		$query = new \Elastica\Query\BoolQuery();
		$query->addFilter( new \Elastica\Query\Term( [ 'coordinates.globe' => $coord->globe ] ) );
		$query->addFilter( new \Elastica\Query\Term( [ 'coordinates.primary' => true ] ) );

		$distanceFilter = new \Elastica\Query\GeoDistance(
			'coordinates.coord',
			[ 'lat' => $coord->lat, 'lon' => $coord->lon ],
			$radius . 'm'
		);
		$query->addFilter( $distanceFilter );

		if ( $docIdToExclude !== '' ) {
			$query->addMustNot( new \Elastica\Query\Term( [ '_id' => $docIdToExclude ] ) );
		}

		$nested = new \Elastica\Query\Nested();
		$nested->setPath( 'coordinates' )->setQuery( $query );

		return $nested;
	}

	/**
	 * @param KeywordFeatureNode $node
	 * @param QueryBuildingContext $context
	 * @return AbstractQuery|null
	 */
	public function getFilterQuery( KeywordFeatureNode $node, QueryBuildingContext $context ) {
		list( $coord, $radius, $excludedPageId ) = $context->getKeywordExpandedData( $node );
		if ( $coord === null ) {
			return null;
		}
		return $this->doGetFilterQuery( $context->getSearchConfig(), $coord, $radius, $excludedPageId );
	}

	/**
	 * @param SearchConfig $config
	 * @param Coord $coord
	 * @param int $radius
	 * @param int $excludedPageId
	 * @return AbstractQuery
	 */
	protected function doGetFilterQuery(
		SearchConfig $config,
		Coord $coord,
		$radius,
		$excludedPageId
	) {
		$excludedDocId = $config->makeId( $excludedPageId );
		return self::createQuery( $coord, $radius, $excludedDocId );
	}
}
