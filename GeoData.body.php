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

	/**
	 * Returns primary coordinates of the given page, if any
	 * @param Title $title
	 * @return Coord|false: Coordinates or false
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
		$res = $db->select( 'geo_tags', array_values( Coord::$fieldMapping ), $conds, __METHOD__ );
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

	public static function getCoordInfo() {
		global $wgContLang;
		static $result = null;
		if ( !$result ) {
			$result = array(
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
				'primary' => array( 'primary' ),
			);
			if ( $wgContLang->getCode() != 'en' ) {
				$result['primary'][] = wfMessage( 'geodata-primary-coordinate' )->plain();
			}
			$result['primary'] = array_flip( $result['primary'] );
		}
		return $result;
	}
}

/**
 * Class representing coordinates
 */
class Coord {
	public $lat, 
		$lon,
		$id,
		$globe,
		$primary = false,
		$dim,
		$type,
		$name,
		$country,
		$region;

	public function __construct( $lat, $lon ) {
		global $wgDefaultGlobe;
		$this->lat = $lat;
		$this->lon = $lon;
		$this->globe = $wgDefaultGlobe;
	}
	
	public static function newFromRow( $row ) {
		global $wgDefaultGlobe;
		$c = new Coord( $row->gt_lat, $row->gt_lon );
		foreach ( self::$fieldMapping as $field => $column ) {
			if ( isset( $row->$column ) ) {
				$c->$field = $row->$column;
			}
		}
		return $c;
	}

	/**
	 * Compares this coordinates with the given coordinates
	 *
	 * @param Coord $coord: Coordinate to compare with
	 * @param int $precision: Comparison precision
	 * @return Boolean
	 */
	public function equalsTo( Coord $coord, $precision = 6 ) {
		return round( $this->lat, $precision ) == round( $coord->lat, $precision )
			&& round( $this->lon, $precision ) == round( $coord->lon, $precision );
	}

	/**
	 * Compares all the fields of this object with the given coordinates object
	 *
	 * @param Coord $coord: Coordinate to compare with
	 * @param int $precision: Comparison precision
	 * @return Boolean
	 */
	public function fullyEqualsTo( Coord $coord, $precision = 6 ) {
		return round( $this->lat, $precision ) == round( $coord->lat, $precision )
			&& round( $this->lon, $precision ) == round( $coord->lon, $precision )
			&& $this->globe == $coord->globe
			&& $this->primary == $coord->primary
			&& $this->dim == $coord->dim
			&& $this->type == $coord->type
			&& $this->name == $coord->name
			&& $this->country == $coord->country
			&& $this->region == $coord->region;
	}

	public function getRow( $pageId = null ) {
		$row =  array( 'gt_page_id' => $pageId );
		foreach ( self::$fieldMapping as $field => $column ) {
			$row[$column] = $this->$field;
		}
		return $row;
	}

	public static $fieldMapping = array(
		'id' => 'gt_id',
		'lat' => 'gt_lat',
		'lon' => 'gt_lon',
		'globe' => 'gt_globe',
		'primary' => 'gt_primary',
		'dim' => 'gt_dim',
		'type' => 'gt_type',
		'name' => 'gt_name',
		'country' => 'gt_country',
		'region' => 'gt_region',
	);
}