<?php
use GeoData\Globe;
use GeoData\Math;

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
}
