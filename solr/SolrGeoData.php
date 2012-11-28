<?php

class SolrGeoData {
	/**
	 * @param bool $master
	 *
	 * @return Solarium_Client
	 */
	public static function newClient( $master = false ) {
		global $wgGeoDataSolrOptions, $wgGeoDataSolrHosts, $wgGeoDataSolrMaster;

		$options = $wgGeoDataSolrOptions;
		if ( $master ) {
			$options['adapteroptions']['host'] = $wgGeoDataSolrMaster;
		} else {
			$options['adapteroptions']['host'] = GeoData::pickRandom( $wgGeoDataSolrHosts );
		}

		return new Solarium_Client( $options );
	}
}
