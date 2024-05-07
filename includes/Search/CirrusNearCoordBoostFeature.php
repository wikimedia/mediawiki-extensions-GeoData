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
use GeoData\Coord;

/**
 * Applies geo boosting to the query by providing coordinates.
 *
 * it increases the score of results within the geographic area. All values can be prefixed
 * with a radius in m or km to apply. If not specified this defaults to 5km.
 *
 * Examples:
 *  boost-nearcoord:-12.345,87.654
 *  boost-nearcoord:77km,34.567,76.543
 */
class CirrusNearCoordBoostFeature extends SimpleKeywordFeature implements BoostFunctionFeature {
	use CirrusGeoFeature;

	/** @inheritDoc */
	protected function getKeywords() {
		return [ 'boost-nearcoord' ];
	}

	/** @inheritDoc */
	protected function doApply( SearchContext $context, $key, $value, $quotedValue, $negated ) {
		[ $coord, $radius ] = $this->parseValue( $key, $value, $quotedValue, '', '', $context );
		if ( $coord !== null ) {
			$context->addCustomRescoreComponent(
				$this->buildBoostFunction( $context->getConfig(), $coord, $radius )
			);
		}

		return [ null, false ];
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @param string $quotedValue
	 * @param string $valueDelimiter
	 * @param string $suffix
	 * @param WarningCollector $warningCollector
	 * @return array{array{lat:float,lon:float,globe:string}|null,int}
	 */
	public function parseValue(
		$key,
		$value,
		$quotedValue,
		$valueDelimiter,
		$suffix,
		WarningCollector $warningCollector
	) {
		return $this->parseGeoNearby( $warningCollector, $key, $value );
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
		[ $coord, $radius ] = $node->getParsedValue();
		if ( $coord !== null ) {
			return $this->buildBoostFunction( $context->getSearchConfig(), $coord, $radius );
		}
		return null;
	}

	/**
	 * @param SearchConfig $config
	 * @param array{lat:float,lon:float,globe:string} $coord
	 * @param int $radius
	 * @return GeoRadiusFunctionScoreBuilder
	 */
	private function buildBoostFunction( SearchConfig $config, array $coord, $radius ) {
		$coordObject = new Coord( $coord['lat'], $coord['lon'], $coord['globe'] );
		return new GeoRadiusFunctionScoreBuilder( $config,
			1, $coordObject, $radius );
	}
}
