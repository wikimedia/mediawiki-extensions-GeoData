<?php

namespace GeoData;

use CirrusSearch\Hooks\CirrusSearchAddQueryFeaturesHook;
use CirrusSearch\SearchConfig;
use GeoData\Search\CirrusNearCoordBoostFeature;
use GeoData\Search\CirrusNearCoordFilterFeature;
use GeoData\Search\CirrusNearTitleBoostFeature;
use GeoData\Search\CirrusNearTitleFilterFeature;

/**
 * Hook handlers
 * All hooks from the CirrusSearch extension which is optional to use with this extension.
 */
class CirrusSearchHooks implements
	CirrusSearchAddQueryFeaturesHook
{
	/**
	 * Add geo-search feature to search syntax
	 * @param SearchConfig $config
	 * @param array &$features
	 */
	public function onCirrusSearchAddQueryFeatures( SearchConfig $config, array &$features ): void {
		$features[] = new CirrusNearTitleBoostFeature( $config );
		$features[] = new CirrusNearTitleFilterFeature( $config );
		$features[] = new CirrusNearCoordBoostFeature( $config );
		$features[] = new CirrusNearCoordFilterFeature( $config );
	}
}
