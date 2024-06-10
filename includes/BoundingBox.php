<?php

namespace GeoData;

/**
 * Class that represents a bounding box
 * Currently, only Earth is supported
 */
class BoundingBox {

	private Coord $coord1;
	private Coord $coord2;

	private function __construct( Coord $topLeft, Coord $bottomRight ) {
		$this->coord1 = $topLeft;
		$this->coord2 = $bottomRight;
	}

	/**
	 * @param float $lat1
	 * @param float $lon1
	 * @param float $lat2
	 * @param float $lon2
	 * @param string $globe
	 */
	public static function newFromNumbers( $lat1, $lon1, $lat2, $lon2, string $globe ): self {
		return new self( new Coord( $lat1, $lon1, $globe ), new Coord( $lat2, $lon2, $globe ) );
	}

	/**
	 * Constructs a bounding box from 2 corner coordinates
	 */
	public static function newFromPoints( Coord $topLeft, Coord $bottomRight ): self {
		return new self( $topLeft, $bottomRight );
	}

	/**
	 * @internal Temporary helper method until we find a better solution
	 */
	public function wrapAround(): void {
		Math::wrapAround( $this->coord1->lat, $this->coord2->lat, -90, 90 );
		// FIXME: This is not correct for other globes!
		Math::wrapAround( $this->coord1->lon, $this->coord2->lon, -180, 180 );
	}

	/**
	 * @return Coord Top left corner of this bounding box
	 */
	public function topLeft(): Coord {
		return $this->coord1;
	}

	/**
	 * @return Coord Bottom right corner of this bounding box
	 */
	public function bottomRight(): Coord {
		return $this->coord2;
	}

	/**
	 * Computes a (very approximate) area of this bounding box
	 *
	 * @return float
	 */
	public function area() {
		$midLat = ( $this->coord2->lat + $this->coord1->lat ) / 2;
		$radius = $this->coord1->getGlobeObj()->getRadius();
		$vert = Math::distance( $this->coord1->lat, 0, $this->coord2->lat, 0, $radius );
		$horz = Math::distance( $midLat, $this->coord1->lon, $midLat, $this->coord2->lon, $radius );

		return $horz * $vert;
	}

	/**
	 * Returns center of this bounding box
	 */
	public function center(): Coord {
		$lon = ( $this->coord2->lon + $this->coord1->lon ) / 2.0;
		if ( $this->coord1->lon > $this->coord2->lon ) {
			// Wrap around
			$lon += ( $lon < 0 ) ? 180 : -180;
		}

		return new Coord( ( $this->coord1->lat + $this->coord2->lat ) / 2.0, $lon, $this->coord1->globe );
	}
}
