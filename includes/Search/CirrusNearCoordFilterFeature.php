<?php


namespace GeoData;

use CirrusSearch\Query\SimpleKeywordFeature;
use CirrusSearch\Search\SearchContext;
use CirrusSearch\WarningCollector;

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
class CirrusNearCoordFilterFeature extends SimpleKeywordFeature {
	use CirrusGeoFeature;

	/**
	 * @var \Config
	 */
	private $config;

	/**
	 * CirrusGeoBoostFeature constructor.
	 * @param \Config $config
	 */
	public function __construct( \Config $config ) {
		$this->config = $config;
	}

	/**
	 * @return string[]
	 */
	protected function getKeywords() {
		return [ 'nearcoord' ];
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
		list( $coord, $radius ) = $this->parseValue( $key, $value, $quotedValue,
			'', '', $context );
		$filter = null;
		if ( $coord !== null ) {
			$coordObject = new Coord( $coord['lat'], $coord['lon'], $coord['globe'] );
			$filter = CirrusNearTitleFilterFeature::createQuery( $coordObject, $radius );
		}
		return [ $filter, false ];
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @param string $quotedValue
	 * @param string $valueDelimiter
	 * @param string $suffix
	 * @param WarningCollector $warningCollector
	 * @return array|false|null
	 */
	public function parseValue(
		$key,
		$value,
		$quotedValue,
		$valueDelimiter,
		$suffix,
		WarningCollector $warningCollector
	) {
		return $this->parseGeoNearby( $warningCollector, $this->config, $key, $value );
	}
}
