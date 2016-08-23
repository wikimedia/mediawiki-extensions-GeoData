<?php

namespace GeoData\Test;

use GeoData\Globe;
use GeoData\Math;
use MediaWikiTestCase;

/**
 * @group GeoData
 */
class GlobeTest extends MediaWikiTestCase {
	public function testEarth() {
		$g = new Globe( 'earth' );
		$this->assertEquals( 'earth', $g->getName() );
		$this->assertTrue( $g->isKnown() );
		$this->assertEquals( -180, $g->getMinLongitude() );
		$this->assertEquals( 180, $g->getMaxLongitude() );
		$this->assertEquals( 1, $g->getEastSign() );
		$this->assertEquals( Math::EARTH_RADIUS, $g->getRadius() );
	}

	public function testUnknown() {
		$g = new Globe( '(unknown globe)' );
		$this->assertEquals( '(unknown globe)', $g->getName() );
		$this->assertFalse( $g->isKnown() );
		$this->assertEquals( -360, $g->getMinLongitude() );
		$this->assertEquals( 360, $g->getMaxLongitude() );
		$this->assertEquals( 1, $g->getEastSign() );
		$this->assertNull( $g->getRadius() );
	}

	/**
	 * @dataProvider provideCoordinatesValidation
	 * @param string $globeName
	 * @param float $lat
	 * @param float $lon
	 * @param bool $expected
	 */
	public function testCoordinatesValidation( $globeName, $lat, $lon, $expected ) {
		$globe = new Globe( $globeName );

		$this->assertEquals( $expected, $globe->coordinatesAreValid( $lat, $lon ) );
	}

	public function provideCoordinatesValidation() {
		return [
			[ 'earth', 0, 0, true ],
			[ 'earth', 90, 180, true ],
			[ 'earth', 90.001, 0, false ],
			[ 'earth', 0, -181, false ],
			[ 'moon', 0, 0, true ],
			[ 'moon', 89, -179, true ],
			[ 'moon', 0, 181, false ],
			[ '(unknown globe)', 0, 0, true ],
			[ '(unknown globe)', -89, -359, true ],
			[ '(unknown globe)', 89, 359, true ],
			[ '(unknown globe)', -91, 0, false ],
			[ '(unknown globe)', 0, 361, false ],
		];
	}
}
