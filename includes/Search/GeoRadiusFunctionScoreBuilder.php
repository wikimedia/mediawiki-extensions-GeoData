<?php
namespace GeoData;

use CirrusSearch\Search\FunctionScoreBuilder;
use CirrusSearch\Search\SearchContext;
use Elastica\Query\FunctionScore;

/**
 * Builds a boost for documents based on geocoordinates.
 * Initialized by special syntax in user query.
 * @see CirrusGeoFeature
 * @package GeoData
 */
class GeoRadiusFunctionScoreBuilder extends FunctionScoreBuilder {
	/**
	 * Default feature weight
	 */
	const DEFAULT_WEIGHT = 2;
	/**
	 * @var Coord
	 */
	private $coord;
	/**
	 * @var int
	 */
	private $radius;

	/**
	 * GeoRadiusFunctionScoreBuilder constructor.
	 * @param SearchContext $context
	 * @param float $weight Used to amend profile weight, e.g. negative boosting
	 * @param Coord $coord Center coordinate
	 * @param int $radius
	 */
	public function __construct( SearchContext $context, $weight, Coord $coord, $radius ) {
		$weightProfile = $context->getConfig()->get( 'GeoDataRadiusScoreOverrides' );
		$weightProfile['value'] = self::DEFAULT_WEIGHT;
		// Overrides will be applied to weight in parent ctor
		parent::__construct( $context, $weightProfile );
		$this->weight *= $weight;
		$this->coord = $coord;
		$this->radius = $radius;
	}

	public function append( FunctionScore $functionScore ) {
		$functionScore->addWeightFunction( $this->weight,
			CirrusGeoFeature::createQuery( $this->coord, $this->radius ) );
	}
}
