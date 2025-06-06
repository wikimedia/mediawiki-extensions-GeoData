<?php

namespace GeoData;

/**
 * Immutable representation of a celestial body
 */
class Globe {

	public const EARTH = 'earth';

	private string $name;

	/** @var float|null */
	private $radius;

	/** @var float Defaults to -360 for unknown globes, or to 0 for known globes */
	private $minLon = -360;

	/** @var float Defaults to +360 for unknown globes */
	private $maxLon = 360;

	/** @var int Sign for values going to the East, either -1 or +1 */
	private $east = +1;

	private bool $known = false;

	/**
	 * @param string $name Internal globe name
	 */
	public function __construct( string $name ) {
		global $wgGlobes;

		$this->name = $name;

		$data = $wgGlobes[$name] ?? self::getData()[$name] ?? null;
		if ( $data !== null ) {
			$this->radius = $data['radius'] ?? null;
			$this->minLon = $data['lon'][0] ?? 0;
			$this->maxLon = $data['lon'][1] ?? $this->minLon + 360;
			$this->east = $data['east'] ?? +1;
			$this->known = true;
		}
	}

	/**
	 * @return array[]
	 */
	private static function getData(): array {
		static $data = [];
		if ( $data ) {
			return $data;
		}

		$earth   = [ 'lon' => [ -180, 180 ], 'east' => +1 ];
		$east360 = [ 'lon' => [ 0, 360 ], 'east' => +1 ];
		$west360 = [ 'lon' => [ 0, 360 ], 'east' => -1 ];

		/**
		 * Format:
		 * 'lon' => Array of minimum and maximum longitude, defaults to [ 0, 360 ]
		 * 'east' => If values going to the East are positive or negative, defaults to +1
		 * 'radius' => mean radius in meters (optional)
		 * Coordinate systems mostly taken from http://planetarynames.wr.usgs.gov/TargetCoordinates
		 * Radii taken from Wikipedia. Globes that are too irregular in shape don't have radius set.
		 */
		$data = [
			self::EARTH => $earth + [ 'radius' => Math::EARTH_RADIUS ],
			'mercury'   => $west360 + [ 'radius' => 2439700.0 ],
			'venus'     => $east360 + [ 'radius' => 6051800.0 ],
			'moon'      => $earth + [ 'radius' => 1737100.0 ],
			// Assuming MDIM 2.1
			'mars'      => $east360 + [ 'radius' => 3389500.0 ],
			'phobos'    => $west360,
			'deimos'    => $west360,
			// 'ceres' => ???,
			// 'vesta' => ???,
			'ganymede'  => $west360 + [ 'radius' => 2634100.0 ],
			'callisto'  => $west360 + [ 'radius' => 2410300.0 ],
			'io'        => $west360 + [ 'radius' => 1821600.0 ],
			'europa'    => $west360 + [ 'radius' => 1560800.0 ],
			'mimas'     => $west360 + [ 'radius' => 198200.0 ],
			'enceladus' => $west360 + [ 'radius' => 252100.0 ],
			'tethys'    => $west360 + [ 'radius' => 531100.0 ],
			'dione'     => $west360 + [ 'radius' => 561400.0 ],
			'rhea'      => $west360 + [ 'radius' => 763800.0 ],
			'titan'     => $west360 + [ 'radius' => 2575500.0 ],
			'hyperion'  => $west360,
			'iapetus'   => $west360 + [ 'radius' => 734500.0 ],
			'phoebe'    => $west360,
			'miranda'   => $east360 + [ 'radius' => 235800.0 ],
			'ariel'     => $east360 + [ 'radius' => 578900.0 ],
			'umbriel'   => $east360 + [ 'radius' => 584700.0 ],
			'titania'   => $east360 + [ 'radius' => 788400.0 ],
			'oberon'    => $east360 + [ 'radius' => 761400.0 ],
			'triton'    => $east360 + [ 'radius' => 1353400.0 ],
			// ???
			'pluto'     => $east360 + [ 'radius' => 1187000.0 ],
		];

		return $data;
	}

	/**
	 * Globe internal name
	 */
	public function getName(): string {
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
	 */
	public function isKnown(): bool {
		return $this->known;
	}

	/**
	 * Compares this globe to another
	 *
	 * @param Globe|string $other
	 * @return bool
	 */
	public function equalsTo( $other ): bool {
		return $this->name === ( $other instanceof Globe ? $other->name : $other );
	}

	/**
	 * Checks whether given coordinates are valid
	 * @param int|float|string $lat
	 * @param int|float|string $lon
	 */
	public function coordinatesAreValid( $lat, $lon ): bool {
		if ( !is_numeric( $lat ) || !is_numeric( $lon ) ) {
			return false;
		}
		$lat = (float)$lat;
		$lon = (float)$lon;

		return $lon >= $this->minLon && $lon <= $this->maxLon
			&& abs( $lat ) <= 90;
	}
}
