<?php

namespace GeoData\Search;

use CirrusSearch\WarningCollector;
use Config;
use GeoData\GeoData;
use GeoData\Globe;
use Title;

/**
 * Trait for geo based features.
 */
trait CirrusGeoFeature {
	/** @var int Default radius, in meters */
	private static $DEFAULT_RADIUS = 5000;

	/**
	 * radius, if provided, must have either m or km suffix. Valid formats:
	 *   <title>
	 *   <radius>,<title>
	 *
	 * @param WarningCollector $warningCollector
	 * @param string $key Key used to trigger feature
	 * @param string $text user input to parse
	 * @return array Three member array with Coordinate object, integer radius
	 *  in meters, and page id to exclude from results.. When invalid the
	 *  Coordinate returned will be null.
	 */
	public function parseGeoNearbyTitle( WarningCollector $warningCollector, $key, $text ) {
		$title = Title::newFromText( $text );
		if ( $title && $title->exists() ) {
			// Default radius if not provided: 5km
			$radius = self::$DEFAULT_RADIUS;
		} else {
			// If the provided value is not a title try to extract a radius prefix
			// from the beginning. If $text has a valid radius prefix see if the
			// remaining text is a valid title to use.
			$pieces = explode( ',', $text, 2 );
			if ( count( $pieces ) !== 2 ) {
				$warningCollector->addWarning(
					"geodata-search-feature-invalid-coordinates",
					$key, $text
				);
				return [ null, 0, '' ];
			}
			$radius = self::parseDistance( $pieces[0] );
			if ( $radius === null ) {
				$warningCollector->addWarning(
					"geodata-search-feature-invalid-distance",
					$key, $pieces[0]
				);
				return [ null, 0, '' ];
			}
			$title = Title::newFromText( $pieces[1] );
			if ( !$title || !$title->exists() ) {
				$warningCollector->addWarning(
					"geodata-search-feature-unknown-title",
					$key, $pieces[1]
				);
				return [ null, 0, '' ];
			}
		}

		$coord = GeoData::getPageCoordinates( $title );
		if ( !$coord ) {
			$warningCollector->addWarning(
				'geodata-search-feature-title-no-coordinates',
				(string)$title
			);
			return [ null, 0, '' ];
		}

		return [ $coord, $radius, $title->getArticleID() ];
	}

	/**
	 * radius, if provided, must have either m or km suffix. Latitude and longitude
	 * must be floats in the domain of [-90:90] for latitude and [-180,180] for
	 * longitude. Valid formats:
	 *   <lat>,<lon>
	 *   <radius>,<lat>,<lon>
	 *
	 * @param WarningCollector $warningCollector
	 * @param Config $config
	 * @param string $key
	 * @param string $text
	 * @return array Two member array with Coordinate object, and integer radius
	 *  in meters. When invalid the Coordinate returned will be null.
	 */
	public function parseGeoNearby(
		WarningCollector $warningCollector,
		Config $config,
		$key,
		$text
	) {
		$pieces = explode( ',', $text, 3 );
		// Default radius if not provided: 5km
		$radius = self::$DEFAULT_RADIUS;
		if ( count( $pieces ) === 3 ) {
			$radius = self::parseDistance( $pieces[0] );
			if ( $radius === null ) {
				$warningCollector->addWarning(
					'geodata-search-feature-invalid-distance',
					$key, $pieces[0]
				);
				return [ null, 0 ];
			}
			list( , $lat, $lon ) = $pieces;
		} elseif ( count( $pieces ) === 2 ) {
			list( $lat, $lon ) = $pieces;
		} else {
			$warningCollector->addWarning(
				'geodata-search-feature-invalid-coordinates',
				$key, $text
			);
			return [ null, 0 ];
		}

		$globe = new Globe( $config->get( 'DefaultGlobe' ) );
		if ( !$globe->coordinatesAreValid( $lat, $lon ) ) {
			$warningCollector->addWarning(
				'geodata-search-feature-invalid-coordinates',
				$key, $text
			);
			return [ null, 0 ];
		}

		return [
			[ 'lat' => floatval( $lat ), 'lon' => floatval( $lon ), 'globe' => $globe->getName() ],
			$radius,
		];
	}

	/**
	 * @param string $distance
	 * @return int|null Parsed distance in meters, or null if unparsable
	 */
	public static function parseDistance( $distance ) {
		if ( !preg_match( '/^(\d+)(m|km|mi|ft|yd)$/', $distance, $matches ) ) {
			return null;
		}

		$scale = [
			'm' => 1,
			'km' => 1000,
			// Supported non-SI units, and their conversions, sourced from
			// https://en.wikipedia.org/wiki/Unit_of_length#Imperial.2FUS
			'mi' => 1609.344,
			'ft' => 0.3048,
			'yd' => 0.9144,
		];

		return max( 10, (int)round( (int)$matches[1] * $scale[$matches[2]] ) );
	}
}
