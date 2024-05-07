<?php

namespace GeoData\Test;

use GeoData\Coord;
use GeoData\Globe;
use GeoData\Math;
use MediaWikiIntegrationTestCase;

/**
 * @covers \GeoData\Coord
 *
 * @todo: More tests
 * @group GeoData
 */
class CoordTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers \GeoData\Coord::equalsTo
	 * @dataProvider provideEquals
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

	public static function provideEquals() {
		$testCases = [
			[
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 123, 'type' => 'not', 'country' => 'not',
					'primary' => true, 'name' => 'not' ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 456, 'type' => 'equal', 'country' => 'equal',
					'primary' => false, 'name' => 'equal' ] ),
				true,
				'Equality with other fields differing',
			],
		];
		return array_merge( $testCases, self::provideAlwaysEqualCoords() );
	}

	private static function provideAlwaysEqualCoords() {
		return [
			[
				new Coord( 10, 20 ),
				new Coord( 10, 20 ),
				true,
				'Basic equality'
			],
			[
				new Coord( 10, 20 ),
				new Coord( 10.0, 20.00000000000001 ),
				true,
				'Basic equality 2: compare floats with ints'
			],
			[
				new Coord( 10, 20 ),
				new Coord( 123, 20 ),
				false,
				'Basic inequality: lat'
			],
			[
				new Coord( 10, 20 ),
				new Coord( 10, 123 ),
				false,
				'Basic inequality: lon'
			],
			[
				new Coord( 10, 20, 'endor' ),
				new Coord( 10, 20, 'endor' ),
				true,
				'Equality with globe set'
			],
			[
				new Coord( 10, 20 ),
				new Coord( 10, 20, 'moon' ),
				false,
				'Inequality due to globe'
			],
			[
				new Coord( 10, 20, 'yavin' ),
				new Coord( 0, 0, 'yavin' ), false,
				'Inequality with globes equal'
			],
			[
				new Coord( 10, 20 ),
				new Coord( 10.1, 20 ),
				false,
				'Precision 1'
			],
			[
				new Coord( 10, 20 ),
				new Coord( 10, 20.0000001 ),
				true,
				'Precision 2'
			],
			[
				new Coord( 10, 20 ),
				null,
				false,
				'Comparison with null'
			],
			[
				new Coord( 10, 20, '01' ),
				new Coord( 10, 20, '1' ),
				false,
				'Compare globes strictly'
			],
			[
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
				'With extra fields'
			]
		];
	}

	/**
	 * Test that serialization-deserialization works.
	 * @dataProvider provideFullyEquals
	 * @covers \GeoData\Coord::jsonSerialize
	 * @covers \GeoData\Coord::newFromJson
	 */
	public function testSerializeDeserialize( Coord $coord ) {
		$deserialized = Coord::newFromJson( $coord->jsonSerialize() );
		$this->assertTrue( $coord->fullyEqualsTo( $deserialized ) );
	}

	/**
	 * @covers \GeoData\Coord::fullyEqualsTo
	 * @dataProvider provideFullyEquals
	 *
	 * @param Coord $coord1
	 * @param Coord $coord2
	 * @param bool $matchExpected
	 * @param string $msg
	 */
	public function testFullyEquals( $coord1, $coord2, $matchExpected, $msg = '' ) {
		$this->assertEquals( $matchExpected, $coord1->fullyEqualsTo( $coord2 ), $msg );
		if ( $coord2 ) {
			$this->assertEquals( $matchExpected, $coord2->fullyEqualsTo( $coord1 ), $msg );
		}
	}

	public static function provideFullyEquals() {
		$testCases = [
			[
				new Coord( 10, 20, Globe::EARTH, [ 'primary' => true ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'primary' => false ] ),
				false,
				'Strict inequality: primary'
			],
			[
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 123 ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 456 ] ),
				false,
				'Strict inequality: dim'
			],
			[
				new Coord( 10, 20, Globe::EARTH, [ 'type' => 'not' ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'type' => 'equal' ] ),
				false,
				'Strict inequality: type'
			],
			[
				new Coord( 10, 20, Globe::EARTH, [ 'name' => 'not' ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'name' => 'equal' ] ),
				false,
				'Strict inequality: name'
			],
			[
				new Coord( 10, 20, Globe::EARTH, [ 'country' => 'not' ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'country' => 'equal' ] ),
				false,
				'Strict inequality: country'
			],
			[
				new Coord( 10, 20, Globe::EARTH, [ 'region' => 'not' ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'region' => 'equal' ] ),
				false,
				'Strict inequality: region'
			],
			// Now make sure comparison is type-aware when needed
			[
				new Coord( 10, 20, Globe::EARTH, [ 'primary' => 'yes' ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'primary' => true ] ),
				true,
				'Strict inequality: compare primary as booleanish'
			],
			[
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 123 ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 123.0 ] ),
				false,
				'Strict inequality: dim'
			],
			[
				new Coord( 10, 20, Globe::EARTH, [ 'type' => '01' ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'type' => '1' ] ),
				false,
				'Strict inequality: type'
			],
			[
				new Coord( 10, 20, Globe::EARTH, [ 'name' => '01' ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'name' => '1' ] ),
				false,
				'Strict inequality: name'
			],
			[
				new Coord( 10, 20, Globe::EARTH, [ 'country' => '01' ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'country' => '1' ] ),
				false,
				'Strict inequality: country'
			],
			[
				new Coord( 10, 20, Globe::EARTH, [ 'region' => '01' ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'region' => '1' ] ),
				false,
				'Strict inequality: region'
			],
			// Null must never match anything
			[
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 0 ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => null ] ),
				false,
				'Strict inequality: dim comparison with null'
			],
			[
				new Coord( 10, 20, Globe::EARTH, [ 'type' => '' ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'type' => null ] ),
				false,
				'Strict inequality: type comparison with null'
			],
			[
				new Coord( 10, 20, Globe::EARTH, [ 'name' => '' ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'name' => null ] ),
				false,
				'Strict inequality: name comparison with null'
			],
			[
				new Coord( 10, 20, Globe::EARTH, [ 'country' => '0' ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'country' => null ] ),
				false,
				'Strict inequality: country comparison with null'
			],
			[
				new Coord( 10, 20, Globe::EARTH, [ 'region' => '0' ] ),
				new Coord( 10, 20, Globe::EARTH, [ 'region' => null ] ),
				false,
				'Strict inequality: region comparison with null'
			],
		];

		return array_merge( self::provideAlwaysEqualCoords(), $testCases );
	}

	/**
	 * @covers \GeoData\Coord::bboxAround
	 */
	public function testBboxAround() {
		for ( $i = 0; $i < 90; $i += 5 ) {
			$coord = new Coord( $i, $i );
			$bbox = $coord->bboxAround( 5000 );
			$this->assertEqualsWithDelta( 10000, Math::distance( $bbox->lat1, $i, $bbox->lat2, $i ),
				1, 'Testing latitude' );
			$this->assertEqualsWithDelta( 10000, Math::distance( $i, $bbox->lon1, $i, $bbox->lon2 ),
				1, 'Testing longitude' );
		}
	}

	/**
	 * @covers \GeoData\Coord::getGlobeObj
	 * @dataProvider provideGlobeObj
	 */
	public function testGlobeObj( string $name, Globe $expected ) {
		$c = new Coord( 10, 20, $name );
		$this->assertTrue( $expected->equalsTo( $c->getGlobeObj() ) );
	}

	public static function provideGlobeObj() {
		return [
			[ Globe::EARTH, new Globe() ],
			[ 'moon', new Globe( 'moon' ) ],
			[ 'something nonexistent', new Globe( 'something nonexistent' ) ],
		];
	}
}
