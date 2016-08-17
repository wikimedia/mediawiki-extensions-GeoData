<?php

namespace GeoData;

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
		$region,

		$pageId,
		$distance;

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
		$this->globe = isset( $globe ) ? $globe : $wgDefaultGlobe;

		foreach ( $extraFields as $key => $value ) {
			if ( isset( self::$fieldMapping[$key] ) ) {
				$this->$key = $value;
			}
		}
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
	 * @return Globe
	 */
	public function getGlobeObj() {
		return new Globe( $this->globe );
	}

	/**
	 * Compares this coordinates with the given coordinates
	 *
	 * @param Coord $coord: Coordinate to compare with
	 * @param int $precision: Comparison precision
	 * @return Boolean
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
	 * @param Coord $coord: Coordinate to compare with
	 * @param int $precision: Comparison precision
	 * @return Boolean
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
	 * @param int $pageId: ID of page associated with this coordinate
	 * @return array: Associative array in format 'field' => 'value'
	 */
	public function getRow( $pageId ) {
		global $wgGeoDataIndexGranularity, $wgGeoDataBackend;
		$row =  [ 'gt_page_id' => $pageId ];
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

	public static function getFieldMapping() {
		return self::$fieldMapping;
	}

	public static function getFields() {
		static $fields = null;
		if ( !$fields ) {
			$fields = array_keys( self::$fieldMapping );
		}
		return $fields;
	}

	public static function getColumns() {
		static $columns = null;
		if ( !$columns ) {
			$columns = array_values( self::$fieldMapping );
		}
		return $columns;
	}
}
