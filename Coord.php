<?php

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

	public function __construct( $lat, $lon, $globe = null ) {
		global $wgDefaultGlobe;

		$this->lat = $lat;
		$this->lon = $lon;
		$this->globe = isset( $globe ) ? $globe : $wgDefaultGlobe;
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
	public function equalsTo( $coord, $precision = 6 ) {
		return isset( $coord )
		&& round( $this->lat, $precision ) == round( $coord->lat, $precision )
		&& round( $this->lon, $precision ) == round( $coord->lon, $precision )
		&& $this->globe == $coord->globe;
	}

	/**
	 * Compares all the fields of this object with the given coordinates object
	 *
	 * @param Coord $coord: Coordinate to compare with
	 * @param int $precision: Comparison precision
	 * @return Boolean
	 */
	public function fullyEqualsTo( $coord, $precision = 6 ) {
		return isset( $coord )
		&& round( $this->lat, $precision ) == round( $coord->lat, $precision )
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

	/**
	 * Returns these coordinates as an associative array
	 * @return array
	 */
	public function getAsArray() {
		$result = array();
		foreach ( self::getFields() as $field ) {
			$result[$field] = $this->$field;
		}
		return $result;
	}

	private static $fieldMapping = array(
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
