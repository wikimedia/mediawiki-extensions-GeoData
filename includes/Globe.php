<?php

namespace GeoData;

/**
 * Immutable representation of a celestial body
 */
class Globe {
	/** @var string */
	private $name;

	/** @var float|null */
	private $radius;

	/** @var float */
	private $minLon;

	/** @var float */
	private $maxLon;

	/** @var int */
	private $east;

	/** @var bool */
	private $known;

	/**
	 * @param string $name Internal globe name
	 */
	public function __construct( $name ) {
		$this->name = $name;

		$globes = self::getData();
		if ( isset( $globes[$name] ) ) {
			$data = $globes[$name];
			$this->radius = isset( $data['radius'] ) ? $data['radius'] : null;
			$this->minLon = $data['lon'][0];
			$this->maxLon = $data['lon'][1];
			$this->east = $data['east'];
			$this->known = true;
		} else {
			$this->minLon = -360;
			$this->maxLon = 360;
			$this->east = 1;
			$this->known = false;
		}
	}

	private static function getData() {
		global $wgGlobes;

		static $data = [];
		if ( $data ) {
			return $data;
		}

		$earth = [ 'lon' => [ -180, 180 ], 'east' => +1 ];
		$east360 = [ 'lon' => [ 0, 360 ], 'east' => +1 ];
		$west360 = [ 'lon' => [ 0, 360 ], 'east' => -1 ];

		// Coordinate systems mostly taken from http://planetarynames.wr.usgs.gov/TargetCoordinates
		$data = [
			'earth' => $earth + [ 'radius' => Math::EARTH_RADIUS ],
			'mercury' => $west360,
			'venus' => $east360,
			'moon' => $earth,
			'mars' => $east360, // Assuming MDIM 2.1
			'phobos' => $west360,
			'deimos' => $west360,
			// 'ceres' => ???,
			// 'vesta' => ???,
			'ganymede' => $west360,
			'callisto' => $west360,
			'io' => $west360,
			'europa' => $west360,
			'mimas' => $west360,
			'enceladus' => $west360,
			'tethys' => $west360,
			'dione' => $west360,
			'rhea' => $west360,
			'titan' => $west360,
			'hyperion' => $west360,
			'iapetus' => $west360,
			'phoebe' => $west360,
			'miranda' => $east360,
			'ariel' => $east360,
			'umbriel' => $east360,
			'titania' => $east360,
			'oberon' => $east360,
			'triton' => $east360,
			'pluto' => $east360, // ???
		];

		$data = $wgGlobes + $data;

		return $data;
	}

	/**
	 * Globe internal name
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Returns globe radius or null if it's not known
	 * @return float|null
	 */
	public function getRadius() {
		return $this->radius;
	}

	/**
	 * Returns minimum longitude
	 * @return float
	 */
	public function getMinLongitude() {
		return $this->minLon;
	}

	/**
	 * Returns maximum longitude
	 * @return float
	 */
	public function getMaxLongitude() {
		return $this->maxLon;
	}

	/**
	 * Returns the sign of East
	 * @return int
	 */
	public function getEastSign() {
		return $this->east;
	}

	/**
	 * Returns whether this globe is registered (and hence, we know its properties)
	 * @return bool
	 */
	public function isKnown() {
		return $this->known;
	}

	/**
	 * Compares this globe to another
	 * @param Globe $other
	 * @return bool
	 */
	public function equalsTo( Globe $other ) {
		return $this->name === $other->name;
	}

	/**
	 * Checks whether given coordinates are valid
	 * @param int $lat
	 * @param int $lon
	 * @return bool
	 */
	public function coordinatesAreValid( $lat, $lon ) {
		if ( !is_numeric( $lat ) || !is_numeric( $lon ) || abs( $lat ) > 90 ) {
			return false;
		}

		return $lon >= $this->minLon && $lon <= $this->maxLon;
	}
}
