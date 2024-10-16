<?php

namespace GeoData\Search;

use CirrusSearch\Parser\AST\KeywordFeatureNode;
use CirrusSearch\Query\Builder\QueryBuildingContext;
use CirrusSearch\Query\FilterQueryFeature;
use CirrusSearch\Query\SimpleKeywordFeature;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\WarningCollector;
use Elastica\Query\AbstractQuery;
use GeoData\Coord;

/**
 * Applies geo filtering to the query by providing coordinates.
 *
 * Limits search results to a geographic area within the geographic area. All values
 * can be prefixed with a radius in m or km to apply. If not specified this defaults
 * to 5km.
 *
 * Examples:
 *  nearcoord:1.2345,-5.4321
 *  nearcoord:17km,54.321,-12.345
 */
class CirrusNearCoordFilterFeature extends SimpleKeywordFeature implements FilterQueryFeature {
	use CirrusGeoFeature;

	/**
	 * @return string[]
	 */
	protected function getKeywords() {
		return [ 'nearcoord' ];
	}

	/** @inheritDoc */
	protected function doApply( SearchContext $context, $key, $value, $quotedValue, $negated ) {
		[ $coord, $radius ] = $this->parseValue( $key, $value, $quotedValue,
			'', '', $context );
		return [ $this->doGetFilterquery( $coord, $radius ), false ];
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
	 * @param array|null $coord
	 * @param int $radius
	 * @return AbstractQuery|null
	 */
	protected function doGetFilterquery( $coord, $radius ) {
		$filter = null;
		if ( $coord !== null ) {
			$coordObject = new Coord( $coord['lat'], $coord['lon'], $coord['globe'] );
			$filter = CirrusNearTitleFilterFeature::createQuery( $coordObject, $radius );
		}

		return $filter;
	}

	/**
	 * @param KeywordFeatureNode $node
	 * @param QueryBuildingContext $context
	 * @return AbstractQuery|null
	 */
	public function getFilterQuery( KeywordFeatureNode $node, QueryBuildingContext $context ) {
		[ $coord, $radius ] = $node->getParsedValue();
		return $this->doGetFilterquery( $coord, $radius );
	}
}
