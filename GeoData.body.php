<?php

class GeoData {
	/**
	 *
	 * @param type $lat
	 * @param type $lon
	 * @return Boolean: Whether the coordinate is valid
	 */
	public static function validateCoord( $lat, $lon ) {
		return is_numeric( $lat )
			&& is_numeric( $lon )
			&& abs( $lat ) <= 90
			&& abs( $lon ) <= 180;
	}

	public static function getPageCoordinates( Title $title ) {
		$dbr = wfGetDB( DB_SLAVE );
		$row = $dbr->selectRow( 'geo_tags', 
			array( 'gt_lat', 'gt_lon' ),
			array( 'gt_page_id' => $title->getArticleID(), 'gt_primary' => 1 )
		);
		if ( !$row ) {
			return false;
		}
		return Coord::newFromRow( $row );
	}

	/**
	 * Parses coordinates
	 * See https://en.wikipedia.org/wiki/Template:Coord for sample inputs
	 * 
	 * @param String $str: 
	 * @returns Status: Status object, in case of success its value is a Coord object.
	 */
	public static function parseCoordinates( $parts ) {
		global $wgContLang;

		$count = count( $parts );
		if ( !is_array( $parts ) || $count < 2 || $count > 8 || ( $count % 2 ) ) {
			return Status::newFatal( 'geodata-bad-input' );
		}
		list( $latArr, $lonArr ) = array_chunk( $parts, $count / 2 );
		$coordInfo = self::getCoordInfo();
		
		$lat = self::parseOneCoord( $latArr, $coordInfo['lat'] );
		if ( $lat === false ) {
			return Status::newFatal( 'geodata-bad-latitude' );
		}
		$lon = self::parseOneCoord( $lonArr, $coordInfo['lon'] );
		if ( $lon === false ) {
			return Status::newFatal( 'geodata-bad-longitude' );
		}
		return Status::newGood( new Coord( $lat, $lon ) );
	}

	private static function parseOneCoord( $parts, $coordInfo ) {
		global $wgContLang;

		$count = count( $parts );
		$multiplier = 1;
		$value = 0;

		for ( $i = 0; $i < $count; $i++ ) {
			$part = $parts[$i];
			if ( $i > 0 && $i == $count - 1 ) {
				$suffix = self::parseSuffix( $part, $coordInfo );
				if ( $suffix ) {
					if ( $value < 0 ) {
						return false; // "-60°S sounds weird, isn't it?
					}
					$value *= $suffix;
					break;
				} elseif ( $i == 3 ) {
					return false;
				}
			}
			$part = $wgContLang->parseFormattedNumber( $part );
			$min = $i == 0 ? -$coordInfo['range'] : 0;
			$max = $i == 0 ? $coordInfo['range'] : 59.999999;
			if ( !is_numeric( $part )
				|| $part < $min
				|| $part > $max ) {
				return false;
			}
			$value += $part * $multiplier * GeoMath::sign( $value );
			$multiplier /= 60;
		}
		if ( abs( $value ) > $coordInfo['range'] ) {
			return false;
		}
		return $value;
	}

	/**
	 * Parses coordinate suffix such as N, S, E or W
	 * 
	 * @param String $str: String to test
	 * @param Array $coordInfo
	 * @return int: Sign modifier or 0 if not a suffix
	 */
	private static function parseSuffix( $str, $coordInfo ) {
		global $wgContLang;
		$str = $wgContLang->uc( trim( $str ) );
		foreach ( $coordInfo['-'] as $suffix ) {
			if ( $suffix == $str ) {
				return -1;
			}
		}
		foreach ( $coordInfo['+'] as $suffix ) {
			if ( $suffix == $str ) {
				return 1;
			}
		}
		return 0;
	}

	/**
	 *
	 * @param Coord $coord
	 * @param Array $args 
	 */
	public static function parseTagArgs( Coord $coord, $args ) {
		$result = $args;
		// fear not of overwriting stuff we've just received from the geohack param, it has minimum precedence
		if ( isset( $args['geohack'] ) ) {
			$result = array_merge( self::parseGeoHackArgs( $args['geohack'] ), $result );
		}
		if ( isset( $args['dim'] ) && is_numeric( $args['dim'] ) && $args['dim'] > 0 ) {
			$coord->dim = $args['dim'];
		} else {
			$coord->dim = null;
		}
		$coord->primary = isset( $args['primary'] );
		return Status::newGood( $result );
	}

	public static function parseGeoHackArgs( $str ) {
		$result = array();
		$parts = explode( '_', $str );
		foreach ( $parts as $arg ) {
			if ( !preg_match( '/(\\S+?):(.*)/', $arg, $matches ) ) {
				continue;
			}
			$key = $m[1];
			$value = $m[2];
			if ( $key == 'dim' ) {
				$result['dim'] = $value;
			}
		}
		return $result;
	}

	private static function getCoordInfo() {
		//@todo: internationalisation?
		return array(
			'lat' => array(
				'range' => 90,
				'+' => array( 'N' ),
				'-' => array( 'S' ),
			),
			'lon' => array(
				'range' => 180,
				'+' => array( 'E' ),
				'-' => array( 'W' ),
			),
		);
	}
}

/**
 * CLass representing one coordinate
 */
class Coord {
	public $lat, 
		$lon,
		$primary,
		$dim,
		$params;

	public function __construct( $lat, $lon ) {
		$this->lat = $lat;
		$this->lon = $lon;
	}

	public static function newFromRow( $row ) {
		return new Coord( $row->gt_lat, $row->gt_lon );
	}

	/**
	 * Compares this coordinate with the given coordinate
	 *
	 * @param Coord $coord: Coordinate to compare with
	 * @param int $precision: Comparison precision
	 * @return Boolean
	 */
	public function equalsTo( Coord $coord, $precision = 6 ) {
		return round( $this->lat, $precision ) == round( $coord->lat, $precision )
			&& round( $this->lon, $precision ) == round( $coord->lon, $precision );
	}

	public function getRow( $pageId = null ) {
		return array(
			'gt_page_id' => $pageId,
			'gt_primary' => $this->primary,
			'gt_lat' => $this->lat,
			'gt_lon' => $this->lon,
			'gt_dim' => $this->dim,
		);
	}
}