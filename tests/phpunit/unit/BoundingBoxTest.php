<?php

namespace GeoData\Test;

use GeoData\BoundingBox;
use GeoData\Coord;
use MediaWikiUnitTestCase;

/**
 * @covers \GeoData\BoundingBox
 *
 * @group GeoData
 */
class BoundingBoxTest extends MediaWikiUnitTestCase {

	public function testNewFromPoints() {
		$coord1 = new Coord( 1.1, 1.2 );
		$coord2 = new Coord( 2.1, 2.2 );
		$bbox = BoundingBox::newFromPoints( $coord1, $coord2 );
		$this->assertEquals( $coord1, $bbox->topLeft() );
		$this->assertEquals( $coord2, $bbox->bottomRight() );
	}

	/**
	 * @dataProvider provideArea
	 */
	public function testArea( int $expected, float $lat1, float $lon1, float $lat2, float $lon2 ) {
		$bbox = new BoundingBox( $lat1, $lon1, $lat2, $lon2 );
		$this->assertSame( $expected, (int)$bbox->area() );
	}

	public static function provideArea() {
		return [
			[ 0, 0, 0, 0, 0 ],
			[ 12364, 0, 0, 0.001, 0.001 ],
			[ 12364, 0, 85, 0.001, 85.001 ],
			[ 6182, 60, 0, 60.001, 0.001 ],
			[ 1077, 85, 0, 85.001, 0.001 ],
			[ 21, 89.9, 0, 89.901, 0.001 ],
		];
	}

	/**
	 * @dataProvider provideCenter
	 */
	public function testCenter( $latExpected, $lonExpected, $lat1, $lon1, $lat2, $lon2 ) {
		$bbox = new BoundingBox( $lat1, $lon1, $lat2, $lon2, 'moon' );
		$center = $bbox->center();
		$this->assertEquals( $latExpected, $center->lat, 'Comparing latitudes...' );
		$this->assertEquals( $lonExpected, $center->lon, 'Comparing longitudes...' );
		$this->assertSame( 'moon', $center->globe );
	}

	public static function provideCenter() {
		return [
			[ 15, 15, 10, 10, 20, 20 ],
			[ 15, -180, 10, 175, 20, -175 ],
			[ 15, -170, 10, 175, 20, -155 ],
			[ 15, 170, 10, 155, 20, -175 ],
		];
	}
}
