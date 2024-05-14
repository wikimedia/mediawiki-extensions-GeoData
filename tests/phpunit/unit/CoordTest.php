<?php

namespace GeoData\Test;

use GeoData\Coord;
use GeoData\Globe;
use GeoData\Math;
use MediaWikiUnitTestCase;

/**
 * @covers \GeoData\Coord
 *
 * @todo: More tests
 * @group GeoData
 */
class CoordTest extends MediaWikiUnitTestCase {

	public function testNewFromRow() {
		$coord = Coord::newFromRow( (object)[
			'gt_lat' => 1,
			'gt_lon' => 2,
			'gt_globe' => 'fantasy',
			'gt_dim' => '5',
		] );
		$this->assertSame( 1.0, $coord->lat );
		$this->assertSame( 2.0, $coord->lon );
		$this->assertSame( 'fantasy', $coord->globe );
		$this->assertFalse( $coord->primary );
		$this->assertSame( 5, $coord->dim );
	}

	/**
	 * @dataProvider provideEquals
	 */
	public function testEquals( Coord $coord1, ?Coord $coord2, bool $matchExpected ) {
		$this->assertEquals( $matchExpected, $coord1->equalsTo( $coord2 ) );
		if ( $coord2 ) {
			$this->assertEquals( $matchExpected, $coord2->equalsTo( $coord1 ) );
		}
	}

	public static function provideEquals() {
		yield 'Equality with other fields differing' => [
			new Coord( 10, 20, Globe::EARTH, [ 'dim' => 123, 'type' => 'not', 'country' => 'not',
				'primary' => true, 'name' => 'not' ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'dim' => 456, 'type' => 'equal', 'country' => 'equal',
				'primary' => false, 'name' => 'equal' ] ),
			true,
		];
		yield from self::provideAlwaysEqualCoords();
	}

	private static function provideAlwaysEqualCoords() {
		yield 'Basic equality' => [
			new Coord( 10, 20 ),
			new Coord( 10, 20 ),
			true,
		];
		yield 'Basic equality 2: compare floats with ints' => [
			new Coord( 10, 20 ),
			new Coord( 10.0, 20.00000000000001 ),
			true,
		];
		yield 'Basic inequality: lat' => [
			new Coord( 10, 20 ),
			new Coord( 123, 20 ),
			false,
		];
		yield 'Basic inequality: lon' => [
			new Coord( 10, 20 ),
			new Coord( 10, 123 ),
			false,
		];
		yield 'Equality with globe set' => [
			new Coord( 10, 20, 'endor' ),
			new Coord( 10, 20, 'endor' ),
			true,
		];
		yield 'Inequality due to globe' => [
			new Coord( 10, 20 ),
			new Coord( 10, 20, 'moon' ),
			false,
		];
		yield 'Inequality with globes equal' => [
			new Coord( 10, 20, 'yavin' ),
			new Coord( 0, 0, 'yavin' ),
			false,
		];
		yield 'Precision 1' => [
			new Coord( 10, 20 ),
			new Coord( 10.1, 20 ),
			false,
		];
		yield 'Precision 2' => [
			new Coord( 10, 20 ),
			new Coord( 10, 20.0000001 ),
			true,
		];
		yield 'Comparison with null' => [
			new Coord( 10, 20 ),
			null,
			false,
		];
		yield 'Compare globes strictly' => [
			new Coord( 10, 20, '01' ),
			new Coord( 10, 20, '1' ),
			false,
		];
		yield 'With extra fields' => [
			new Coord( 10, 20, '01', [
				'id' => 1,
				'primary' => true,
				'dim' => 2,
				'type' => 'testing',
				'name' => 'very testing',
				'country' => 'Russia',
				'region' => 'world',
			] ),
			new Coord( 10, 20, '01', [
				'id' => 1,
				'primary' => true,
				'dim' => 2,
				'type' => 'testing',
				'name' => 'very testing',
				'country' => 'Russia',
				'region' => 'world',
			] ),
			true,
		];
	}

	/**
	 * Test that serialization-deserialization works.
	 * @dataProvider provideFullyEquals
	 */
	public function testSerializeDeserialize( Coord $coord ) {
		$deserialized = Coord::newFromJson( $coord->jsonSerialize() );
		$this->assertTrue( $coord->fullyEqualsTo( $deserialized ) );
	}

	/**
	 * @dataProvider provideFullyEquals
	 */
	public function testFullyEquals( Coord $coord1, ?Coord $coord2, bool $matchExpected ) {
		$this->assertEquals( $matchExpected, $coord1->fullyEqualsTo( $coord2 ) );
		if ( $coord2 ) {
			$this->assertEquals( $matchExpected, $coord2->fullyEqualsTo( $coord1 ) );
		}
	}

	public static function provideFullyEquals() {
		yield from self::provideAlwaysEqualCoords();
		yield 'Strict inequality: primary' => [
			new Coord( 10, 20, Globe::EARTH, [ 'primary' => true ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'primary' => false ] ),
			false,
		];
		yield 'Strict inequality: dim comparison with different values' => [
			new Coord( 10, 20, Globe::EARTH, [ 'dim' => 123 ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'dim' => 456 ] ),
			false,
		];
		yield 'Strict inequality: type comparison with different values' => [
			new Coord( 10, 20, Globe::EARTH, [ 'type' => 'not' ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'type' => 'equal' ] ),
			false,
		];
		yield 'Strict inequality: name comparison with different values' => [
			new Coord( 10, 20, Globe::EARTH, [ 'name' => 'not' ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'name' => 'equal' ] ),
			false,
		];
		yield 'Strict inequality: country comparison with different values' => [
			new Coord( 10, 20, Globe::EARTH, [ 'country' => 'not' ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'country' => 'equal' ] ),
			false,
		];
		yield 'Strict inequality: region comparison with different values' => [
			new Coord( 10, 20, Globe::EARTH, [ 'region' => 'not' ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'region' => 'equal' ] ),
			false,
		];
		// Now make sure comparison is type-aware when needed
		yield 'Strict inequality: compare primary as booleanish' => [
			new Coord( 10, 20, Globe::EARTH, [ 'primary' => 'yes' ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'primary' => true ] ),
			true,
		];
		yield 'Strict inequality: dim comparison with different types' => [
			new Coord( 10, 20, Globe::EARTH, [ 'dim' => 123 ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'dim' => 123.0 ] ),
			false,
		];
		yield 'Strict inequality: type comparison with numeric strings' => [
			new Coord( 10, 20, Globe::EARTH, [ 'type' => '01' ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'type' => '1' ] ),
			false,
		];
		yield 'Strict inequality: name comparison with numeric strings' => [
			new Coord( 10, 20, Globe::EARTH, [ 'name' => '01' ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'name' => '1' ] ),
			false,
		];
		yield 'Strict inequality: country comparison with numeric strings' => [
			new Coord( 10, 20, Globe::EARTH, [ 'country' => '01' ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'country' => '1' ] ),
			false,
		];
		yield 'Strict inequality: region comparison with numeric strings' => [
			new Coord( 10, 20, Globe::EARTH, [ 'region' => '01' ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'region' => '1' ] ),
			false,
		];
		// Null must never match anything
		yield 'Strict inequality: dim comparison with null' => [
			new Coord( 10, 20, Globe::EARTH, [ 'dim' => 0 ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'dim' => null ] ),
			false,
		];
		yield 'Strict inequality: type comparison with null' => [
			new Coord( 10, 20, Globe::EARTH, [ 'type' => '' ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'type' => null ] ),
			false,
		];
		yield 'Strict inequality: name comparison with null' => [
			new Coord( 10, 20, Globe::EARTH, [ 'name' => '' ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'name' => null ] ),
			false,
		];
		yield 'Strict inequality: country comparison with null' => [
			new Coord( 10, 20, Globe::EARTH, [ 'country' => '0' ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'country' => null ] ),
			false,
		];
		yield 'Strict inequality: region comparison with null' => [
			new Coord( 10, 20, Globe::EARTH, [ 'region' => '0' ] ),
			new Coord( 10, 20, Globe::EARTH, [ 'region' => null ] ),
			false,
		];
	}

	public function testBboxAround() {
		for ( $i = 0; $i < 90; $i += 5 ) {
			$coord = new Coord( $i, $i );
			$bbox = $coord->bboxAround( 5000 );
			$radius = $coord->getGlobeObj()->getRadius();
			$this->assertEqualsWithDelta( 10000,
				Math::distance( $bbox->lat1, $i, $bbox->lat2, $i, $radius ),
				1, 'Testing latitude' );
			$this->assertEqualsWithDelta( 10000,
				Math::distance( $i, $bbox->lon1, $i, $bbox->lon2, $radius ),
				1, 'Testing longitude' );
		}
	}

	/**
	 * @dataProvider provideBoundingBoxes
	 */
	public function testBoundingBoxesWithGlobes( string $globe, float $expected ) {
		$coord = new Coord( 0, 0, $globe );
		$bbox = $coord->bboxAround( 5000 );
		$this->assertEqualsWithDelta( $expected, $bbox->lat2, 0.001 );
		$this->assertEqualsWithDelta( $expected, $bbox->lon2, 0.001 );
	}

	public function provideBoundingBoxes() {
		return [
			[ Globe::EARTH, 0.045 ],
			[ 'mars', 0.085 ],
			[ 'moon', 0.165 ],
		];
	}

	/**
	 * @dataProvider provideGlobeObj
	 */
	public function testGlobeObj( string $name, Globe $expected ) {
		$c = new Coord( 10, 20, $name );
		$this->assertTrue( $expected->equalsTo( $c->getGlobeObj() ) );
		$this->assertTrue( $c->isValid() );
	}

	public static function provideGlobeObj() {
		return [
			[ Globe::EARTH, new Globe() ],
			[ 'moon', new Globe( 'moon' ) ],
			[ 'something nonexistent', new Globe( 'something nonexistent' ) ],
		];
	}

	public function testGetRow() {
		$coord = new Coord( 1.234, 9.876, 'mars' );
		$row = $coord->getRow( 9, 100 );
		$this->assertSame( 9, $row['gt_page_id'] );
		$this->assertSame( 'mars', $row['gt_globe'] );
		$this->assertSame( 123, $row['gt_lat_int'] );
		$this->assertSame( 988, $row['gt_lon_int'] );

		$row = $coord->getRow( 1000, null );
		$this->assertArrayNotHasKey( 'gt_lat_int', $row );
	}

}
