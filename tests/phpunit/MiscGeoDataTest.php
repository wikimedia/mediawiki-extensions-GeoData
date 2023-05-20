<?php

namespace GeoData\Test;

use GeoData\Api\QueryGeoSearchDb;
use MediaWikiIntegrationTestCase;

/**
 * @group GeoData
 */
class MiscGeoDataTest extends MediaWikiIntegrationTestCase {
	/**
	 * @covers \GeoData\Api\QueryGeoSearchDb::intRange
	 * @dataProvider provideIntRangeData
	 */
	public function testIntRange( $min, $max, $expected ) {
		$this->assertEquals( $expected, QueryGeoSearchDb::intRange( $min, $max ) );
	}

	public static function provideIntRangeData() {
		return [
			[ 37.697, 37.877, [ 377, 378, 379 ] ],
			[ 9.99, 10.01, [ 100 ] ],
			[ 179.9, -179.9, [ -1800, -1799, 1799, 1800 ] ]
		];
	}
}
