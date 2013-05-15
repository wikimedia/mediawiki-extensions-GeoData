<?php

class GeoData {
	/**
	 *
	 * @param float $lat
	 * @param float $lon
	 * @param string $globe
	 * @return bool: Whether the coordinate is valid
	 */
	public static function validateCoord( $lat, $lon, $globe ) {
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

	/**
	 * Adds an update job if needed
	 */
	public static function maybeUpdate() {
		global $wgGeoDataBackend, $wgGeoDataUpdatesViaJob;

		if ( $wgGeoDataBackend == 'solr' && $wgGeoDataUpdatesViaJob ) {
			JobQueueGroup::singleton()->push( new SolrUpdateJob( null ) );
		}
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

	/**
	 * Returns this object's representation suitable for insertion into the DB via Databse::insert()
	 * @param int $pageId: ID of page associated with this coordinate
	 * @return Array: Associative array in format 'field' => 'value'
	 */
	public function getRow( $pageId ) {
		global $wgGeoDataIndexGranularity, $wgGeoDataBackend;
		$row =  array( 'gt_page_id' => $pageId );
		foreach ( self::$fieldMapping as $field => $column ) {
			$row[$column] = $this->$field;
		}
		if ( $wgGeoDataBackend == 'db' ) {
			$row['gt_lat_int'] = round( $this->lat * $wgGeoDataIndexGranularity );
			$row['gt_lon_int'] = round( $this->lon * $wgGeoDataIndexGranularity );
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
