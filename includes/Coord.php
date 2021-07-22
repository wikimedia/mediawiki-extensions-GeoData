<?php

namespace GeoData;

use JsonSerializable;

/**
 * Class representing coordinates
 */
class Coord implements JsonSerializable {
	/** @var float Latitude of the point in degrees */
	public $lat;

	/** @var float Longitude of the point in degrees */
	public $lon;

	/** @var int Tag id, needed for selective replacement and paging */
	public $id;

	/** @var string Name of planet or other astronomic body on which the coordinates reside */
	public $globe;

	/** @var bool Whether this coordinate is primary
	 * (defines the principal location of article subject) or secondary (just mentioned in text)
	 */
	public $primary = false;

	/** @var int|null Approximate viewing radius in meters, gives an idea how large the object is */
	public $dim;

	/** @var string|null Type of the point */
	public $type;

	/** @var string|null Point name on the map */
	public $name;

	/** @var string|null Two character ISO 3166-1 alpha-2 country code */
	public $country;

	/** @var string|null Second part of ISO 3166-2 region code, up to 3 alphanumeric chars */
	public $region;

	/** @var int */
	public $pageId;

	/** @var float Distance in metres */
	public $distance;

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param string|null $globe
	 * @param array $extraFields
	 */
	public function __construct( $lat, $lon, $globe = null, $extraFields = [] ) {
		global $wgDefaultGlobe;

		$this->lat = $lat;
		$this->lon = $lon;
		$this->globe = $globe ?? $wgDefaultGlobe;

		foreach ( $extraFields as $key => $value ) {
			if ( isset( self::$fieldMapping[$key] ) ) {
				$this->$key = $value;
			}
		}
	}

	/**
	 * Constructs a Coord object from a database row
	 *
	 * @param \stdClass $row
	 * @return Coord
	 */
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
	 * @return Globe
	 */
	public function getGlobeObj() {
		return new Globe( $this->globe );
	}

	/**
	 * Compares this coordinates with the given coordinates
	 *
	 * @param Coord $coord Coordinate to compare with
	 * @param int $precision Comparison precision
	 * @return bool
	 */
	public function equalsTo( $coord, $precision = 6 ) {
		return isset( $coord )
			&& round( $this->lat, $precision ) == round( $coord->lat, $precision )
			&& round( $this->lon, $precision ) == round( $coord->lon, $precision )
			&& $this->globe === $coord->globe;
	}

	/**
	 * Compares all the fields of this object with the given coordinates object
	 *
	 * @param Coord $coord Coordinate to compare with
	 * @param int $precision Comparison precision
	 * @return bool
	 */
	public function fullyEqualsTo( $coord, $precision = 6 ) {
		return $this->equalsTo( $coord, $precision )
			&& $this->primary == $coord->primary
			&& $this->dim === $coord->dim
			&& $this->type === $coord->type
			&& $this->name === $coord->name
			&& $this->country === $coord->country
			&& $this->region === $coord->region;
	}

	/**
	 * Checks whether current coordinates are within current globe's allowed range
	 *
	 * @return bool
	 */
	public function isValid() {
		return $this->getGlobeObj()->coordinatesAreValid( $this->lat, $this->lon );
	}

	/**
	 * Returns a bounding rectangle around this coordinate
	 *
	 * @param float $radius
	 * @return BoundingBox
	 */
	public function bboxAround( $radius ) {
		if ( $radius <= 0 ) {
			return new BoundingBox( $this->lat, $this->lon, $this->lat, $this->lon, $this->globe );
		}
		$r2lat = rad2deg( $radius / Math::EARTH_RADIUS );
		// @todo: doesn't work around poles, should we care?
		if ( abs( $this->lat ) < 89.9 ) {
			$r2lon = rad2deg( $radius / cos( deg2rad( $this->lat ) ) / Math::EARTH_RADIUS );
		} else {
			$r2lon = 0.1;
		}
		$res = new BoundingBox( $this->lat - $r2lat,
			$this->lon - $r2lon,
			$this->lat + $r2lat,
			$this->lon + $r2lon,
			$this->globe
		);
		Math::wrapAround( $res->lat1, $res->lat2, -90, 90 );
		Math::wrapAround( $res->lon1, $res->lon2, -180, 180 );
		return $res;
	}

	/**
	 * Returns a distance from these coordinates to another ones
	 *
	 * @param Coord $coord
	 * @return float Distance in metres
	 */
	public function distanceTo( Coord $coord ) {
		return Math::distance( $this->lat, $this->lon, $coord->lat, $coord->lon );
	}

	/**
	 * Returns this object's representation suitable for insertion into the DB via Databse::insert()
	 * @param int $pageId ID of page associated with this coordinate
	 * @return array Associative array in format 'field' => 'value'
	 */
	public function getRow( $pageId ) {
		global $wgGeoDataIndexGranularity, $wgGeoDataBackend;
		$row = [ 'gt_page_id' => $pageId ];
		foreach ( self::$fieldMapping as $field => $column ) {
			$row[$column] = $this->$field;
		}
		if ( $wgGeoDataBackend == 'db' ) {
			$row['gt_lat_int'] = round( $this->lat * $wgGeoDataIndexGranularity );
			$row['gt_lon_int'] = round( $this->lon * $wgGeoDataIndexGranularity );
		}
		return $row;
	}

	/**
	 * Returns these coordinates as an associative array
	 * @return array
	 */
	public function getAsArray() {
		$result = [];
		foreach ( self::getFields() as $field ) {
			$result[$field] = $this->$field;
		}
		return $result;
	}

	/** @var string[] */
	private static $fieldMapping = [
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
	];

	/**
	 * Returns a mapping from properties of this class to database columns
	 *
	 * @return string[]
	 */
	public static function getFieldMapping() {
		return self::$fieldMapping;
	}

	/**
	 * Returns names of properties of this class that are saved to database
	 *
	 * @return string[]
	 */
	public static function getFields() {
		static $fields = null;
		if ( !$fields ) {
			$fields = array_keys( self::$fieldMapping );
		}
		return $fields;
	}

	/**
	 * Returns names of database columns used to store properties of this class
	 *
	 * @return string[]
	 */
	public static function getColumns() {
		static $columns = null;
		if ( !$columns ) {
			$columns = array_values( self::$fieldMapping );
		}
		return $columns;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return $this->getAsArray();
	}

	/**
	 * Instantiate a Coord from $json array created with self::jsonSerialize.
	 *
	 * @internal
	 * @see self::jsonSerialize
	 * @param array $json
	 * @return static
	 */
	public static function newFromJson( array $json ): self {
		return new Coord(
			$json['lat'],
			$json['lon'],
			$json['globe'],
			$json
		);
	}
}
