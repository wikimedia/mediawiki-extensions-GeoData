<?php

class GeoData {
	/**
	 *
	 * @param float $lat
	 * @param float $lon
	 * @param string $globe
	 * @return bool Whether the coordinate is valid
	 */
	public static function validateCoord( $lat, $lon, $globe = 'earth' ) {
		global $wgGlobes;
		if ( !is_numeric( $lat ) || !is_numeric( $lon ) || abs( $lat ) > 90 ) {
			return false;
		}
		if ( !isset( $wgGlobes[$globe] ) ) {
			return abs( $lon ) <= 360;
		} else {
			return $lon >= $wgGlobes[$globe]['min'] && $lon <= $wgGlobes[$globe]['max'];
		}
	}

	/**
	 * Returns primary coordinates of the given page, if any
	 * @param Title $title
	 * @return Coord|bool: Coordinates or false
	 */
	public static function getPageCoordinates( Title $title ) {
		$coords = self::getAllCoordinates( $title->getArticleID(), array( 'gt_primary' => 1 ) );
		if ( $coords ) {
			return $coords[0];
		}
		return false;
	}

	/**
	 * Retrieves all coordinates for the given page id
	 *
	 * @param int $pageId: ID of the page
	 * @param Array $conds: Conditions for Database::select()
	 * @param int $dbType: Database to select from DM_MASTER or DB_SLAVE
	 * @return Array: Array of Coord objects
	 */
	public static function getAllCoordinates( $pageId, $conds = array(), $dbType = DB_SLAVE ) {
		$db = wfGetDB( $dbType );
		$conds['gt_page_id'] = $pageId;
		$res = $db->select( 'geo_tags', Coord::getColumns(), $conds, __METHOD__ );
		$coords = array();
		foreach ( $res as $row ) {
			$coords[] = Coord::newFromRow( $row );
		}
		return $coords;
	}

	/**
	 * Parses coordinates
	 * See https://en.wikipedia.org/wiki/Template:Coord for sample inputs
	 *
	 * @param Array $parts: Array of coordinate components
	 * @param String $globe: Globe name
	 * @return Status: Status object, in case of success its value is a Coord object.
	 */
	public static function parseCoordinates( $parts, $globe ) {
		global $wgGlobes;

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
		$lonInfo = isset( $wgGlobes[$globe] )
			? $wgGlobes[$globe]
			: array(
				'min' => -360,
				'mid' => 0,
				'max' => 360,
				'abbr' => array( 'E' => 1, 'W' => -1 ),
				'wrap' => true,
			);
		$lon = self::parseOneCoord( $lonArr, $lonInfo );
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
		$alreadyFractional = false;

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
			// 20° 15.5' 20" is wrong
			if ( $alreadyFractional && $part ) {
				return false;
			}
			if ( !is_numeric( $part ) ) {
				$part = $wgContLang->parseFormattedNumber( $part );
			}
			$min = $i == 0 ? $coordInfo['min'] : 0;
			$max = $i == 0 ? $coordInfo['max'] : 59.999999;
			if ( !is_numeric( $part )
				|| $part < $min
				|| $part > $max ) {
				return false;
			}
			$alreadyFractional = $part != intval( $part );
			$value += $part * $multiplier * GeoDataMath::sign( $value );
			$multiplier /= 60;
		}
		if ( $coordInfo['wrap']  && $value < 0 ) {
			$value = $coordInfo['max'] + $value;
		}
		if ( $value < $coordInfo['min'] || $value > $coordInfo['max'] ) {
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
	public static function parseSuffix( $str, $coordInfo ) {
		global $wgContLang;
		$str = $wgContLang->uc( trim( $str ) );
		return isset( $coordInfo['abbr'][$str] ) ? $coordInfo['abbr'][$str] : 0;
	}

	public static function getCoordInfo() {
		static $result = null;
		if ( !$result ) {
			$result = array(
				'lat' => array(
					'min' => -90,
					'mid' => 0,
					'max' => 90,
					'abbr' => array( 'N' => 1, 'S' => -1 ),
					'wrap' => false,
				),
				'primary' => array( 'primary' ),
			);
		}
		return $result;
	}

	/**
	 * Given an array of non-normalised probabilities, this function will select
	 * an element and return the appropriate key.
	 *
	 * @param $weights array
	 *
	 * @return int
	 */
	public static function pickRandom( $weights ) {
		return ArrayUtils::pickRandom( $weights );
	}
}
