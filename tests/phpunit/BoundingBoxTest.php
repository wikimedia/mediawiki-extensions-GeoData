<?php

namespace GeoData\Test;

use GeoData\BoundingBox;
use MediaWikiTestCase;

/**
 * @group GeoData
 */
class BoundingBoxTest extends MediaWikiTestCase {
	/**
	 * @dataProvider provideCenter
	 */
	public function testCenter( $latExpected, $lonExpected, $lat1, $lon1, $lat2, $lon2 ) {
		$bbox = new BoundingBox( $lat1, $lon1, $lat2, $lon2 );
		$center = $bbox->center();
		$this->assertEquals( $latExpected, $center->lat, 'Comparing latitudes...' );
		$this->assertEquals( $lonExpected, $center->lon, 'Comparing longitudes...' );
	}

	public function provideCenter() {
		return [
			[ 15, 15, 10, 10, 20, 20 ],
			[ 15, -180, 10, 175, 20, -175 ],
			[ 15, -170, 10, 175, 20, -155 ],
			[ 15, 170, 10, 155, 20, -175 ],
		];
	}
}