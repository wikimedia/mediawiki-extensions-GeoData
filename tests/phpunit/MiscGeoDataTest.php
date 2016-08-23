<?php

namespace GeoData\Test;

use GeoData\ApiQueryGeoSearchDb;
use MediaWikiTestCase;

/**
 * @group GeoData
 */
class MiscGeoDataTest extends MediaWikiTestCase {
	/**
	 * @dataProvider getIntRangeData
	 */
	public function testIntRange( $min, $max, $expected ) {
		$this->assertEquals( $expected, ApiQueryGeoSearchDb::intRange( $min, $max ) );
	}

	public function getIntRangeData() {
		return [
			[ 37.697, 37.877, [ 377, 378, 379 ] ],
			[ 9.99, 10.01, [ 100 ] ],
			[ 179.9, -179.9, [ -1800, -1799, 1799, 1800 ] ]
		];
	}
}
