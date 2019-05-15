<?php

namespace GeoData\Search;

use CirrusSearch\Parser\AST\KeywordFeatureNode;
use CirrusSearch\Query\BoostFunctionFeature;
use CirrusSearch\Query\Builder\QueryBuildingContext;
use CirrusSearch\Query\SimpleKeywordFeature;
use CirrusSearch\Search\Rescore\BoostFunctionBuilder;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\SearchConfig;
use CirrusSearch\WarningCollector;
use Config;

/**
 * Applies geo boosting to the query by providing a Title.
 *
 * it increases the score of results within the geographic area. All values can be prefixed
 * with a radius in m or km to apply. If not specified this defaults to 5km.
 *
 * Examples:
 *  boost-neartitle:"San Francisco"
 *  boost-neartitle:50km,Kampala
 */
class CirrusNearTitleBoostFeature extends SimpleKeywordFeature implements BoostFunctionFeature {
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
	 * @return array|string[]
	 */
	protected function getKeywords() {
		return [ 'boost-neartitle' ];
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
		list( $coord, $radius ) = $this->parseGeoNearbyTitle( $context, $key, $value );
		if ( $coord ) {
			$context->addCustomRescoreComponent(
				new GeoRadiusFunctionScoreBuilder( $context->getConfig(), $negated ? 0.1 : 1, $coord, $radius )
			);
		}

		return [ null, false ];
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
	 * @param KeywordFeatureNode $node
	 * @param QueryBuildingContext $context
	 * @return BoostFunctionBuilder|null
	 */
	public function getBoostFunctionBuilder(
		KeywordFeatureNode $node,
		QueryBuildingContext $context
	) {
		list( $coord, $radius ) = $context->getKeywordExpandedData( $node );
		if ( $coord !== null ) {
			return new GeoRadiusFunctionScoreBuilder( $context->getSearchConfig(), 1,
				$coord, $radius );
		}
		return null;
	}
}
