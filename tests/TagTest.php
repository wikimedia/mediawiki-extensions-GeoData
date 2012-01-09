<?php

/**
 * @group GeoData
 */
class TagTest extends MediaWikiTestCase {
	/**
	 * @dataProvider getData
	 */
	public function testTagParsing( $input, $expected ) {
		$p = new Parser();
		$opt = new ParserOptions();
		$out = $p->parse( $input, Title::newMainPage(), $opt );
		$this->assertTrue( isset( $out->geoData ) );
		if ( !$expected ) {
			$this->assertFalse( $out->geoData['primary'] );
			$this->assertEmpty( $out->geoData['secondary'] );
			return;
		}
		$all = $out->geoData->getAll();
		$coord = $all[0];
		foreach ( $expected as $field => $value ) {
			$this->assertEquals( $value, $coord->$field, "Checking field $field" );
		}
	}

	public function getData() {
		return array(
			array(
				'{{#coordinates: 10|20|primary}}', 
				array( 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'primary' => true ),
			),
			array(
				'{{#coordinates: 10| primary		|	20}}', 
				array( 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'primary' => true ),
			),
			array(
				'{{#coordinates: primary|10|20}}', 
				array( 'lat' => 10, 'lon' => 20, 'globe' => 'earth', 'primary' => true ),
			),
			array( 
				'{{#coordinates:10|20|globe:Moon dim:10_region:RU-mos}}',
				array( 'lat' => 10, 'lon' => 20, 'globe' => 'moon', 'country' => 'RU', 'region' => 'MOS' ),
			),
		);
	}
}