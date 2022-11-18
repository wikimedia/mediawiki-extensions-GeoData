<?php

namespace GeoData;

use JsonSerializable;

/**
 * Class representing coordinates
 */
class Coord implements JsonSerializable {

	/** Mapping from properties of this class to database columns */
	public const FIELD_MAPPING = [
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

		$this->lat = (float)$lat;
		$this->lon = (float)$lon;
		$this->globe = $globe ?? $wgDefaultGlobe;

		foreach ( $extraFields as $key => $value ) {
			if ( isset( self::FIELD_MAPPING[$key] ) ) {
				$this->$key = $value;
			}
		}
	}

	/**
	 * Constructs a Coord object from a database row
	 *
	 * @param \stdClass $row
	 * @return self
	 */
	public static function newFromRow( $row ): self {
		$c = new self(
			(float)$row->gt_lat,
			(float)$row->gt_lon,
			$row->gt_globe ?? null
		);

		$c->id = $row->gt_id ?? 0;
		$c->primary = (bool)( $row->gt_primary ?? false );
		$c->dim = ( $row->gt_dim ?? null ) === null ? null : (int)$row->gt_dim;
		$c->type = $row->gt_type ?? null;
		$c->name = $row->gt_name ?? null;
		$c->country = $row->gt_country ?? null;
		$c->region = $row->gt_region ?? null;

		return $c;
	}

	/**
	 * @return Globe
	 */
	public function getGlobeObj(): Globe {
		return new Globe( $this->globe );
	}

	/**
	 * Compares this coordinates with the given coordinates
	 *
	 * @param self|null $coord Coordinate to compare with
	 * @param int $precision Comparison precision
	 * @return bool
	 */
	public function equalsTo( $coord, $precision = 6 ): bool {
		return isset( $coord )
			&& round( $this->lat, $precision ) == round( $coord->lat, $precision )
			&& round( $this->lon, $precision ) == round( $coord->lon, $precision )
			&& $this->globe === $coord->globe;
	}

	/**
	 * Compares all the fields of this object with the given coordinates object
	 *
	 * @param self $coord Coordinate to compare with
	 * @param int $precision Comparison precision
	 * @return bool
	 */
	public function fullyEqualsTo( $coord, $precision = 6 ): bool {
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
	public function isValid(): bool {
		return $this->getGlobeObj()->coordinatesAreValid( $this->lat, $this->lon );
	}

	/**
	 * Returns a bounding rectangle around this coordinate
	 *
	 * @param float $radius
	 * @return BoundingBox
	 */
	public function bboxAround( $radius ): BoundingBox {
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
	public function getRow( $pageId ): array {
		global $wgGeoDataIndexGranularity, $wgGeoDataBackend;
		$row = [ 'gt_page_id' => $pageId ];
		foreach ( self::FIELD_MAPPING as $field => $column ) {
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
	public function getAsArray(): array {
		$result = [];
		foreach ( self::FIELD_MAPPING as $field => $_ ) {
			$result[$field] = $this->$field;
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return $this->getAsArray();
	}

	/**
	 * Instantiate a Coord from $json array created with self::jsonSerialize.
	 *
	 * @internal
	 * @see jsonSerialize
	 * @param array $json
	 * @return self
	 */
	public static function newFromJson( array $json ): self {
		return new self(
			$json['lat'],
			$json['lon'],
			$json['globe'],
			$json
		);
	}
}
