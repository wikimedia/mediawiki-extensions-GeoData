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

	/** Name of planet or other astronomic body on which the coordinates reside */
	public string $globe;

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

	/** @var float Distance in meters */
	public $distance;

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param Globe|string $globe
	 * @param array<string,mixed> $extraFields
	 */
	public function __construct( $lat, $lon, $globe = Globe::EARTH, $extraFields = [] ) {
		$this->lat = (float)$lat;
		$this->lon = (float)$lon;
		$this->globe = $globe instanceof Globe ? $globe->getName() : $globe;

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
			$row->gt_globe
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

	public function getGlobeObj(): Globe {
		return new Globe( $this->globe );
	}

	/**
	 * @param self|Globe|string $other
	 * @return bool
	 */
	public function sameGlobe( $other ): bool {
		if ( $other instanceof self ) {
			$other = $other->globe;
		}
		return $this->getGlobeObj()->equalsTo( $other );
	}

	/**
	 * Compares this coordinates with the given coordinates
	 *
	 * @param self|null $other Coordinate to compare with
	 * @param int $precision Comparison precision
	 * @return bool
	 */
	public function equalsTo( $other, $precision = 6 ): bool {
		return $other !== null
			&& round( $this->lat, $precision ) == round( $other->lat, $precision )
			&& round( $this->lon, $precision ) == round( $other->lon, $precision )
			&& $this->sameGlobe( $other );
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
	 */
	public function isValid(): bool {
		return $this->getGlobeObj()->coordinatesAreValid( $this->lat, $this->lon );
	}

	/**
	 * Calculates the distance between this and another pair of coordinates.
	 *
	 * @param Coord $coord
	 * @return float Distance in meters
	 */
	public function distanceTo( Coord $coord ): float {
		return Math::distance( $this->lat, $this->lon, $coord->lat, $coord->lon,
			$this->getGlobeObj()->getRadius() );
	}

	/**
	 * Returns this object's representation suitable for insertion into the DB via Databse::insert()
	 *
	 * @param int $pageId ID of page associated with this coordinate
	 * @param int|null $indexGranularity E.g. 10 for 1/10 of a degree
	 * @return array Associative array in format 'field' => 'value'
	 */
	public function getRow( $pageId, $indexGranularity ): array {
		$row = [ 'gt_page_id' => $pageId ];
		foreach ( self::FIELD_MAPPING as $field => $column ) {
			$row[$column] = $this->$field;
		}
		if ( $indexGranularity ) {
			$row['gt_lat_int'] = (int)round( $this->lat * $indexGranularity );
			$row['gt_lon_int'] = (int)round( $this->lon * $indexGranularity );
		}
		return $row;
	}

	/**
	 * Returns these coordinates as an associative array
	 */
	public function getAsArray(): array {
		$result = [];
		foreach ( self::FIELD_MAPPING as $field => $_ ) {
			$result[$field] = $this->$field;
		}
		return $result;
	}

	public function jsonSerialize(): array {
		return $this->getAsArray();
	}

	/**
	 * Instantiate a Coord from $json array created with self::jsonSerialize.
	 *
	 * @internal
	 * @see jsonSerialize
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
