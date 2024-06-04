<?php

namespace GeoData\Test;

use GeoData\Globe;
use GeoData\Math;
use MediaWikiIntegrationTestCase;

/**
 * @covers \GeoData\Globe
 *
 * @group GeoData
 */
class GlobeTest extends MediaWikiIntegrationTestCase {

	public function testGlobalGlobesConfiguration() {
		$this->setMwGlobals( 'wgGlobes', [ 'fantasy' => [ 'lon' => [ 0, 99 ] ] ] );
		$globe = new Globe( 'fantasy' );
		$this->assertTrue( $globe->isKnown() );
		$this->assertSame( 99, $globe->getMaxLongitude() );
	}

	public function testEarth() {
		$g = new Globe();
		$this->assertEquals( Globe::EARTH, $g->getName() );
		$this->assertTrue( $g->isKnown() );
		$this->assertEquals( -180, $g->getMinLongitude() );
		$this->assertEquals( 180, $g->getMaxLongitude() );
		$this->assertSame( 1, $g->getEastSign() );
		$this->assertSame( Math::EARTH_RADIUS, $g->getRadius() );
		$this->assertTrue( $g->equalsTo( new Globe() ) );
	}

	public function testMars() {
		$g = new Globe( 'mars' );
		$this->assertEquals( 'mars', $g->getName() );
		$this->assertTrue( $g->isKnown() );
		$this->assertSame( 0, $g->getMinLongitude() );
		$this->assertEquals( 360, $g->getMaxLongitude() );
		$this->assertSame( 1, $g->getEastSign() );
		$this->assertSame( 3389500.0, $g->getRadius() );
	}

	public function testUnknown() {
		$g = new Globe( '(unknown globe)' );
		$this->assertEquals( '(unknown globe)', $g->getName() );
		$this->assertFalse( $g->isKnown() );
		$this->assertEquals( -360, $g->getMinLongitude() );
		$this->assertEquals( 360, $g->getMaxLongitude() );
		$this->assertSame( 1, $g->getEastSign() );
		$this->assertNull( $g->getRadius() );
	}

	/**
	 * @dataProvider provideCoordinatesValidation
	 */
	public function testCoordinatesValidation( string $globeName, $lat, $lon, bool $expected ) {
		$globe = new Globe( $globeName );

		$this->assertSame( $expected, $globe->coordinatesAreValid( $lat, $lon ) );
	}

	public static function provideCoordinatesValidation() {
		return [
			[ Globe::EARTH, 'not a number', 0, false ],
			[ Globe::EARTH, 0, 0, true ],
			[ Globe::EARTH, 90, 180, true ],
			[ Globe::EARTH, 90.001, 0, false ],
			[ Globe::EARTH, 0, -181, false ],
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
