<?php

namespace GeoData;

/**
 * Class that performs basic coordinate calculations
 * Note that the formulas are useful only for our specific purposes, some of them may be
 * inaccurate for long distances. Oh well.
 *
 * All the functions that accept coordinates assume that they're in degrees, not radians.
 */
class Math {
	public const EARTH_RADIUS = 6371010.0;

	/**
	 * Calculates distance between two coordinates
	 * @see https://en.wikipedia.org/wiki/Haversine_formula
	 *
	 * @param float $lat1
	 * @param float $lon1
	 * @param float $lat2
	 * @param float $lon2
	 * @return float Distance in meters
	 */
	public static function distance( $lat1, $lon1, $lat2, $lon2 ) {
		$lat1 = deg2rad( $lat1 );
		$lon1 = deg2rad( $lon1 );
		$lat2 = deg2rad( $lat2 );
		$lon2 = deg2rad( $lon2 );
		$sin1 = sin( ( $lat2 - $lat1 ) / 2 );
		$sin2 = sin( ( $lon2 - $lon1 ) / 2 );
		return 2 * self::EARTH_RADIUS *
			asin( sqrt( $sin1 * $sin1 + cos( $lat1 ) * cos( $lat2 ) * $sin2 * $sin2 ) );
	}

	/**
	 * Wraps coordinate values around globe boundaries
	 *
	 * @param float &$from
	 * @param float &$to
	 * @param float $min
	 * @param float $max
	 */
	public static function wrapAround( &$from, &$to, $min, $max ) {
		if ( $from < $min ) {
			$from = $max - ( $min - $from );
		}
		if ( $to > $max ) {
			$to = $min + $to - $max;
		}
	}

	/**
	 * Sign function
	 *
	 * @param float $x Value to get sign of
	 * @return int
	 */
	public static function sign( $x ) {
		if ( $x >= 0 ) {
			return 1;
		}
		return -1;
	}
}
