<?php

namespace GeoData\Test;

use GeoData\Coord;
use MediaWikiTestCase;
use Parser;
use ParserOptions;
use Title;

/**
 * @group GeoData
 */
class TagTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();
		$this->setMwGlobals( 'wgDefaultDim', 1000 ); // reset to default
	}

	private function setWarnings( $level ) {
		global $wgGeoDataWarningLevel;

		$this->setMwGlobals( 'wgGeoDataWarningLevel',
			array_fill_keys( array_keys( $wgGeoDataWarningLevel ), $level )
		);
	}

	private function assertParse( $input, $expected ) {
		$p = new Parser();
		$opt = new ParserOptions();
		$out = $p->parse( $input, Title::newMainPage(), $opt );
		$this->assertTrue( isset( $out->geoData ) );
		if ( !$expected ) {
			$this->assertEmpty( $out->geoData->getAll(),
				'Expected a failure but a result was found: ' . print_r( $out->geoData->getAll(), true )
			);
			return;
		}
		$all = $out->geoData->getAll();
		$this->assertEquals( 1, count( $all ), 'A result was expected, but there was error: ' . strip_tags( $out->getText() ) );
		/** @var Coord $coord */
		$coord = $all[0];
		$this->assertTrue( $coord->fullyEqualsTo( $expected ),
			'Comparing ' . print_r( $coord, true ) . ' against expected ' . print_r( $expected, true )
		);
	}

	/**
	 * @dataProvider getLooseData
	 */
	public function testLooseTagParsing( $input, $expected, $langCode = false ) {
		if ( $langCode ) {
			$this->setContentLang( $langCode );
		}
		$this->setWarnings( 'none' );
		$this->assertParse( $input, $expected );
	}

	/**
	 * @dataProvider getStrictData
	 */
	public function testStrictTagParsing( $input, $expected ) {
		$this->setWarnings( 'fail' );
		$this->assertParse( $input, $expected );
	}

	public function getLooseData() {
		return [
			// Basics
			[
				'{{#coordinates: 10|20|primary}}', 
				new Coord( 10, 20, 'earth', [ 'primary' => true, 'dim' => 1000 ] ),
			],
			[
				'{{#coordinates: 100|20|primary}}', 
				false,
			],
			[
				'{{#coordinates: 10|2000|primary}}', 
				false,
			],
			[
				'{{#coordinates: 10| primary		|	20}}', 
				new Coord( 10, 20, 'earth', [ 'primary' => true, 'dim' => 1000 ] ),
			],
			[ // empty parameter instead of primary
				'{{#coordinates: 10 | |	20 }}', 
				new Coord( 10, 20, 'earth', [ 'primary' => false, 'dim' => 1000 ] ),
			],
			[
				'{{#coordinates: primary|10|20}}', 
				new Coord( 10, 20, 'earth', [ 'primary' => true, 'dim' => 1000 ] ),
			],
			// type
			[
				'{{#coordinates: 10|20|type:landmark}}',
				new Coord( 10, 20, 'earth', [ 'type' => 'landmark', 'dim' => 1000 ] ),
			],
			[
				'{{#coordinates: 10|20|type:city(666)}}', 
				new Coord( 10, 20, 'earth', [ 'type' => 'city', 'dim' => 10000 ] ),
			],
			// Other geohack params
			[
				'{{#coordinates: 10|20}}', 
				new Coord( 10, 20, 'earth',  [ 'dim' => 1000 ] ),
			],
			[ 
				'{{#coordinates:10|20|globe:Moon dim:10_region:RU-mos}}',
				new Coord( 10, 20, 'moon', [ 'country' => 'RU', 'region' => 'MOS', 'dim' => 10 ] ),
			],
			[ 
				'{{#coordinates:10|20|globe:Moon dim:10_region:RU}}',
				new Coord( 10, 20, 'moon', [ 'country' => 'RU', 'dim' => 10 ] ),
			],
			[
				'{{#coordinates: 10|20|_dim:3Km_}}', 
				new Coord( 10, 20, 'earth', [ 'dim' => 3000 ] ),
			],
			[
				'{{#coordinates: 10|20|foo:bar dim:100m}}', 
				new Coord( 10, 20, 'earth', [ 'dim' => 100 ] ),
			],
			[
				'{{#coordinates: 10|20|dim:-300}}', 
				new Coord( 10, 20, 'earth', [ 'dim' => 1000 ] ),
			],
			[
				'{{#coordinates: 10|20|dim:-10km}}', 
				new Coord( 10, 20, 'earth', [ 'dim' => 1000 ] ),
			],
			[
				'{{#coordinates: 10|20|dim:1L}}', 
				new Coord( 10, 20, 'earth', [ 'dim' => 1000 ] ),
			],
			// dim fallbacks
			[
				'{{#coordinates: 10|20|type:city}}', 
				new Coord( 10, 20, 'earth', [ 'type' => 'city', 'dim' => 10000 ] ),
			],
			[
				'{{#coordinates: 10|20|type:city(2000)}}', 
				new Coord( 10, 20, 'earth', [ 'type' => 'city', 'dim' => 10000 ] ),
			],
			[
				'{{#coordinates: 10|20|type:lulz}}', 
				new Coord( 10, 20, 'earth', [ 'type' => 'lulz', 'dim' => 1000 ] ),
			],
			[
				'{{#coordinates: 10|20|scale:50000}}', 
				new Coord( 10, 20, 'earth', [ 'dim' => 5000 ] ),
			],
			// https://phabricator.wikimedia.org/T48181
			[
				'{{#coordinates: 2.5|3,5}}',
				new Coord( 2.5, 3.5, 'earth', [ 'dim' => 1000 ] ),
				'de',
			],
			// https://phabricator.wikimedia.org/T49090
			[
				'{{#coordinates: -3.29237|-60.624889|globe=}}',
				new Coord( -3.29237, -60.624889, 'earth', [ 'dim' => 1000 ] ),
			],
			// Lowercase type
			[
				'{{#coordinates: 10|20|type:sOmEtHiNg}}',
				new Coord( 10, 20, 'earth', [ 'type' => 'something', 'dim' => 1000 ] ),
			],
		];
	}

	public function getStrictData() {
		return [
			[
				'{{#coordinates:10|20|globe:Moon dim:10_region:RUS-MOS}}',
				false,
			],
			[
				'{{#coordinates:10|20|globe:Moon dim:10_region:RU-}}',
				false,
			],
			[
				'{{#coordinates:10|20|globe:Moon dim:10|region=RU-longvalue}}',
				false,
			],
			[
				'{{#coordinates:10|20|globe:Moon dim:10_region:РУ-МОС}}',
				false,
			],
		];
	}
}
