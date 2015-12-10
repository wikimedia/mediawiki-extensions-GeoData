<?php

/**
 * @group GeoData
 */
class GeoDataMathTest extends MediaWikiTestCase {
	/**
	 * @dataProvider getDistanceData
	 */
	public function testDistance( $lat1, $lon1, $lat2, $lon2, $dist, $name ) {
		$this->assertEquals( $dist, GeoDataMath::distance( $lat1, $lon1, $lat2, $lon2 ), "testDistance():  $name", $dist / 1000 );
	}

	public function getDistanceData() {
		return array(
			// just run against a few values from teh internets...
			array( 55.75, 37.6167, 59.95, 30.3167, 635000, 'Moscow to St. Bumtown' ),
			array( 51.5, -0.1167, 52.35, 4.9167, 357520, 'London to Amsterdam' ),
			array( 40.7142, -74.0064, 37.775, -122.418, 4125910, 'New York to San Francisco' ),
			array( 0, 179, 0, -179, 222390, 'Wrap around zero' ),
		);
	}

	/**
	 * @dataProvider getRectData
	 * @todo: test directly now that this function is public
	 */
	public function testRectWrapAround( $lon ) {
		$coord = new Coord( 20, $lon );
		$bbox = $coord->bboxAround( 10000 );
		$this->assertGreaterThan( $bbox->lon2, $bbox->lon1 );
		$this->assertGreaterThanOrEqual( -180, $bbox->lon1 );
		$this->assertLessThanOrEqual( 180, $bbox->lon2 );
	}

	public function getRectData() {
		return array(
			array( 180 ),
			array( -180 ),
			array( 179.95 ),
			array( -179.95 ),
		);
	}
}
