<?php

namespace GeoData;

/**
 * Class that represents a bounding box
 * Currently, only Earth is supported
 */
class BoundingBox {
	public $lat1, $lon1, $lat2, $lon2;
	public $globe;

	public function __construct( $lat1, $lon1, $lat2, $lon2, $globe = 'earth' ) {
		$this->lat1 = $lat1;
		$this->lon1 = $lon1;
		$this->lat2 = $lat2;
		$this->lon2 = $lon2;
		$this->globe = $globe;
	}

	/**
	 * Constructs a bounding box from 2 corner coordinates
	 *
	 * @param Coord $topLeft
	 * @param Coord $bottomRight
	 * @return BoundingBox
	 */
	public static function newFromPoints( Coord $topLeft, Coord $bottomRight ) {
		return new self( $topLeft->lat, $topLeft->lon, $bottomRight->lat, $bottomRight->lon, $topLeft->globe );
	}

	public function topLeft() {
		return new Coord( $this->lat1, $this->lon1, $this->globe );
	}

	public function bottomRight() {
		return new Coord( $this->lat2, $this->lon2, $this->globe );
	}

	/**
	 * Computes a (very approximate) area of this bounding box
	 *
	 * @return float
	 */
	public function area() {
		$midLat = ( $this->lat2 + $this->lat1 ) / 2;
		$vert = Math::distance( $this->lat1, 0, $this->lat2, 0 );
		$horz = Math::distance( $midLat, $this->lon1, $midLat, $this->lon2 );

		return $horz * $vert;
	}

	/**
	 * Returns center of this bounding box
	 *
	 * @return Coord
	 */
	public function center() {
		$lon = ( $this->lon2 + $this->lon1 ) / 2.0;
		if ( $this->lon1 > $this->lon2 ) {
			// Wrap around
			$lon += ( $lon < 0 ) ? 180 : -180;
		}

		return new Coord( ( $this->lat1 + $this->lat2 ) / 2.0, $lon );
	}
}
