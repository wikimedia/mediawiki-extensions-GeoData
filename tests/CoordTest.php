<?php
/**
 * @todo: More tests
 * @group GeoData
 */
class CoordTest extends MediaWikiTestCase {
	/**
	 * @dataProvider getEqualsCases
	 * @param Coord $coord1
	 * @param Coord $coord2
	 * @param bool $matchExpected
	 * @param string $msg
	 */
	public function testEquals( $coord1, $coord2, $matchExpected, $msg = '' ) {
		$this->assertEquals( $matchExpected, $coord1->equalsTo( $coord2 ), $msg );
		if ( $coord2 ) {
			$this->assertEquals( $matchExpected, $coord2->equalsTo( $coord1 ), $msg );
		}
	}

	public function getEqualsCases() {
		return array(
			array( new Coord( 10, 20 ), new Coord( 10, 20 ), true, 'Basic equality' ),
			array( new Coord( 10, 20 ), new Coord( 0, 0 ), false, 'Basic inequality' ),
			array( new Coord( 10, 20, 'endor' ), new Coord( 10, 20, 'endor' ), true, 'Equality with globe set' ),
			array( new Coord( 10, 20, 'earth' ), new Coord( 10, 20, 'moon' ), false, 'Inequality due to globe' ),
			array( new Coord( 10, 20, 'yavin' ), new Coord( 0, 0, 'yavin' ), false, 'Inequality with globes equal' ),
			array( new Coord( 10, 20 ), new Coord( 10, 20.1 ), false, 'Precision 1' ),
			array( new Coord( 10, 20 ), new Coord( 10, 20.0000001 ), true, 'Precision 2' ),
			array( new Coord( 10, 20 ), null, false, 'Comparison with null' ),
		);
	}
}