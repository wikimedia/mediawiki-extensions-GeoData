<?php

namespace GeoData\Test;

use GeoData\Coord;
use GeoData\Math;
use MediaWikiUnitTestCase;

/**
 * @covers \GeoData\Math
 *
 * @group GeoData
 */
class MathTest extends MediaWikiUnitTestCase {
	/**
	 * @dataProvider provideDistanceData
	 */
	public function testDistance( $lat1, $lon1, $lat2, $lon2, $dist ) {
		$this->assertEqualsWithDelta( $dist, Math::distance( $lat1, $lon1, $lat2, $lon2 ),
			$dist / 1000 );
	}

	public static function provideDistanceData() {
		return [
			// just run against a few values from the internets...
			'Moscow to St. Bumtown' => [ 55.75, 37.6167, 59.95, 30.3167, 635000 ],
			'London to Amsterdam' => [ 51.5, -0.1167, 52.35, 4.9167, 357520 ],
			'New York to San Francisco' => [ 40.7142, -74.0064, 37.775, -122.418, 4125910 ],
			'Wrap around zero' => [ 0, 179, 0, -179, 222390 ],
		];
	}

	/**
	 * @dataProvider provideWrapAroundPairs
	 */
	public function testWrapAround( array $coord, array $expected ) {
		Math::wrapAround( $coord[0], $coord[1], -180, 180 );
		$this->assertSame( $expected, $coord );
	}

	public static function provideWrapAroundPairs() {
		return [
			[ [ +000.0, +000.0 ], [ +000.0, +000.0 ] ],
			[ [ -180.0, +179.0 ], [ -180.0, +179.0 ] ],
			[ [ -180.0, +180.0 ], [ -180.0, +180.0 ] ],
			[ [ -181.0, +182.0 ], [ +179.0, -178.0 ] ],
			[ [ -361.0, +361.0 ], [ -001.0, +001.0 ] ],
			[ [ -538.0, +538.0 ], [ -178.0, +178.0 ] ],
			[ [ -722.0, +722.0 ], [ -002.0, +002.0 ] ],
		];
	}

	/**
	 * @dataProvider provideRectData
	 * @todo test directly now that this function is public
	 */
	public function testRectWrapAround( $lon ) {
		$coord = new Coord( 20, $lon );
		$bbox = $coord->bboxAround( 10000 );
		$this->assertGreaterThan( $bbox->lon2, $bbox->lon1 );
		$this->assertGreaterThanOrEqual( -180, $bbox->lon1 );
		$this->assertLessThanOrEqual( 180, $bbox->lon2 );
	}

	public static function provideRectData() {
		return [
			[ 180 ],
			[ -180 ],
			[ 179.95 ],
			[ -179.95 ],
		];
	}

	/**
	 * @dataProvider provideSignedValues
	 */
	public function testSign( float $value, int $expected ) {
		$this->assertSame( $expected, Math::sign( $value ) );
	}

	public static function provideSignedValues() {
		return [
			[ 0.0, 1 ],
			[ -0.0, 1 ],
			[ 0.02, 1 ],
			[ -0.02, -1 ],
			[ 300, 1 ],
			[ -300, -1 ],
		];
	}
}
