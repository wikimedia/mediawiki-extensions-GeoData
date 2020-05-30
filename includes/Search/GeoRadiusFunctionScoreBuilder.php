<?php

namespace GeoData\Search;

use CirrusSearch\Search\Rescore\FunctionScoreBuilder;
use CirrusSearch\SearchConfig;
use Elastica\Query\FunctionScore;
use GeoData\Coord;

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
	private const DEFAULT_WEIGHT = 2;
	/**
	 * @var Coord
	 */
	private $coord;
	/**
	 * @var int
	 */
	private $radius;

	/**
	 * @param SearchConfig $config
	 * @param float $weight Used to amend profile weight, e.g. negative boosting
	 * @param Coord $coord Center coordinate
	 * @param int $radius
	 */
	public function __construct( SearchConfig $config, $weight, Coord $coord, $radius ) {
		$weightProfile = $config->get( 'GeoDataRadiusScoreOverrides' );
		$weightProfile['value'] = self::DEFAULT_WEIGHT;
		// Overrides will be applied to weight in parent ctor
		parent::__construct( $config, $weightProfile );
		$this->weight *= $weight;
		$this->coord = $coord;
		$this->radius = $radius;
	}

	/**
	 * @param FunctionScore $functionScore
	 */
	public function append( FunctionScore $functionScore ) {
		$functionScore->addWeightFunction( $this->weight,
			CirrusNearTitleFilterFeature::createQuery( $this->coord, $this->radius ) );
	}
}
