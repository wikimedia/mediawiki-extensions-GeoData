<?php

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
		$coord = $all[0];
		foreach ( $expected as $field => $value ) {
			$this->assertEquals( $value, $coord->$field, "Checking field $field" );
		}
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
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'primary' => true ],
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
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'primary' => true ],
			],
			[ // empty parameter instead of primary
				'{{#coordinates: 10 | |	20 }}', 
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'primary' => false ],
			],
			[
				'{{#coordinates: primary|10|20}}', 
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'primary' => true ],
			],
			// type
			[
				'{{#coordinates: 10|20|type:city}}', 
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'type' => 'city' ],
			],
			[
				'{{#coordinates: 10|20|type:city(666)}}', 
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'type' => 'city' ],
			],
			// Other geohack params
			[
				'{{#coordinates: 10|20}}', 
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'dim' => 1000 ],
			],
			[ 
				'{{#coordinates:10|20|globe:Moon dim:10_region:RU-mos}}',
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'moon', 'country' => 'RU', 'region' => 'MOS', 'dim' => 10 ],
			],
			[ 
				'{{#coordinates:10|20|globe:Moon dim:10_region:RU}}',
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'moon', 'country' => 'RU', 'dim' => 10 ],
			],
			[
				'{{#coordinates: 10|20|_dim:3Km_}}', 
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'dim' => 3000 ],
			],
			[
				'{{#coordinates: 10|20|foo:bar dim:100m}}', 
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'dim' => 100 ],
			],
			[
				'{{#coordinates: 10|20|dim:-300}}', 
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'dim' => 1000 ],
			],
			[
				'{{#coordinates: 10|20|dim:-10km}}', 
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'dim' => 1000 ],
			],
			[
				'{{#coordinates: 10|20|dim:1L}}', 
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'dim' => 1000 ],
			],
			// dim fallbacks
			[
				'{{#coordinates: 10|20|type:city}}', 
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'type' => 'city', 'dim' => 10000 ],
			],
			[
				'{{#coordinates: 10|20|type:city(2000)}}', 
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'type' => 'city', 'dim' => 10000 ],
			],
			[
				'{{#coordinates: 10|20|type:lulz}}', 
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'type' => 'lulz', 'dim' => 1000 ],
			],
			[
				'{{#coordinates: 10|20|scale:50000}}', 
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'dim' => 5000 ],
			],
			// https://phabricator.wikimedia.org/T48181
			[
				'{{#coordinates: 2.5|3,5}}',
				[ 'lat' => 2.5, 'lon' => 3.5 ],
				'de',
			],
			// https://phabricator.wikimedia.org/T49090
			[
				'{{#coordinates: -3.29237|-60.624889|globe=}}',
				[ 'lat' => -3.29237, 'lon' => -60.624889, 'globe' => 'earth' ],
			],
			// Lowercase type
			[
				'{{#coordinates: 10|20|type:sOmEtHiNg}}',
				[ 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'type' => 'something' ],
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
