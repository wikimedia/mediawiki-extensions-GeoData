<?php

namespace GeoData\Test;

use GeoData\Coord;
use GeoData\CoordinatesOutput;
use GeoData\Globe;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use ParserOptions;

/**
 * @covers \GeoData\CoordinatesParserFunction
 * @covers \GeoData\CoordinatesOutput::getOrBuildFromParserOutput()
 * @covers \GeoData\CoordinatesOutput::getFromParserOutput()
 * @group GeoData
 */
class TagTest extends MediaWikiIntegrationTestCase {

	public function setUp(): void {
		parent::setUp();
		// reset to default
		$this->overrideConfigValue( 'DefaultDim', 1000 );
		$this->clearHooks( [ 'ParserAfterTidy' ] );
	}

	private function assertParse( string $input, ?Coord $expected ): void {
		$p = MediaWikiServices::getInstance()->getParserFactory()->getInstance();
		$opt = ParserOptions::newFromAnon();
		$title = Title::makeTitle( NS_MAIN, __METHOD__ );
		$title->setContentModel( CONTENT_MODEL_WIKITEXT );
		$out = $p->parse( $input, $title, $opt );
		if ( !$expected ) {
			$this->assertNull( CoordinatesOutput::getFromParserOutput( $out ),
				'Expected a failure but a result was found: ' .
					print_r( CoordinatesOutput::getFromParserOutput( $out ), true )
			);
			return;
		}
		$this->assertNotNull( CoordinatesOutput::getFromParserOutput( $out ) );
		$all = CoordinatesOutput::getFromParserOutput( $out )->getAll();
		$this->assertCount( 1, $all,
			'A result was expected, but there was error: ' . strip_tags( $out->getText() ) );
		/** @var Coord $coord */
		$coord = $all[0];
		$this->assertTrue( $coord->fullyEqualsTo( $expected ),
			'Comparing ' . print_r( $coord, true ) .
				' against expected ' . print_r( $expected, true )
		);
	}

	public function testCoordinatesOutput() {
		$output = new ParserOutput();
		$inExtData = new CoordinatesOutput();
		$output->setExtensionData( CoordinatesOutput::GEO_DATA_COORDS_OUTPUT, $inExtData );
		$this->assertSame( $inExtData, CoordinatesOutput::getFromParserOutput( $output ) );

		$output = new ParserOutput();
		$this->assertNotNull( CoordinatesOutput::getOrBuildFromParserOutput( $output ) );
	}

	/**
	 * @dataProvider provideLooseData
	 */
	public function testLooseTagParsing( string $input, ?Coord $expected, string $langCode = null ) {
		if ( $langCode ) {
			$this->setContentLang( $langCode );
		}
		$this->overrideConfigValue( 'GeoDataWarningLevel', [] );
		$this->assertParse( $input, $expected );
	}

	/**
	 * @dataProvider provideStrictData
	 */
	public function testStrictTagParsing( string $input, ?Coord $expected ) {
		$this->overrideConfigValue( 'GeoDataWarningLevel', [ 'invalid region' => 'fail' ] );
		$this->assertParse( $input, $expected );
	}

	public static function provideLooseData() {
		return [
			// Basics
			[
				'{{#coordinates: 10|20|primary}}',
				new Coord( 10, 20, Globe::EARTH, [ 'primary' => true, 'dim' => 1000 ] ),
			],
			[
				'{{#coordinates: 100|20|primary}}',
				null,
			],
			[
				'{{#coordinates: 10|2000|primary}}',
				null,
			],
			[
				'{{#coordinates: 10| primary		|	20}}',
				new Coord( 10, 20, Globe::EARTH, [ 'primary' => true, 'dim' => 1000 ] ),
			],
			[
				// empty parameter instead of primary
				'{{#coordinates: 10 | |	20 }}',
				new Coord( 10, 20, Globe::EARTH, [ 'primary' => false, 'dim' => 1000 ] ),
			],
			[
				'{{#coordinates: primary|10|20}}',
				new Coord( 10, 20, Globe::EARTH, [ 'primary' => true, 'dim' => 1000 ] ),
			],
			// type
			[
				'{{#coordinates: 10|20|type:landmark}}',
				new Coord( 10, 20, Globe::EARTH, [ 'type' => 'landmark', 'dim' => 1000 ] ),
			],
			[
				'{{#coordinates: 10|20|type:city(666)}}',
				new Coord( 10, 20, Globe::EARTH, [ 'type' => 'city', 'dim' => 10000 ] ),
			],
			// Other geohack params
			[
				'{{#coordinates: 10|20}}',
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 1000 ] ),
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
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 3000 ] ),
			],
			[
				'{{#coordinates: 10|20|foo:bar dim:100m}}',
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 100 ] ),
			],
			[
				'{{#coordinates: 10|20|dim:-300}}',
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 1000 ] ),
			],
			[
				'{{#coordinates: 10|20|dim:-10km}}',
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 1000 ] ),
			],
			[
				'{{#coordinates: 10|20|dim:1L}}',
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 1000 ] ),
			],
			// dim fallbacks
			[
				'{{#coordinates: 10|20|type:city}}',
				new Coord( 10, 20, Globe::EARTH, [ 'type' => 'city', 'dim' => 10000 ] ),
			],
			[
				'{{#coordinates: 10|20|type:city(2000)}}',
				new Coord( 10, 20, Globe::EARTH, [ 'type' => 'city', 'dim' => 10000 ] ),
			],
			[
				'{{#coordinates: 10|20|type:lulz}}',
				new Coord( 10, 20, Globe::EARTH, [ 'type' => 'lulz', 'dim' => 1000 ] ),
			],
			[
				'{{#coordinates: 10|20|scale:50000}}',
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 5000 ] ),
			],
			// https://phabricator.wikimedia.org/T48181
			[
				'{{#coordinates: 2.5|3,5}}',
				new Coord( 2.5, 3.5, Globe::EARTH, [ 'dim' => 1000 ] ),
				'de',
			],
			// https://phabricator.wikimedia.org/T49090
			[
				'{{#coordinates: -3.29237|-60.624889|globe=}}',
				new Coord( -3.29237, -60.624889, Globe::EARTH, [ 'dim' => 1000 ] ),
			],
			// Lowercase type
			[
				'{{#coordinates: 10|20|type:sOmEtHiNg}}',
				new Coord( 10, 20, Globe::EARTH, [ 'type' => 'something', 'dim' => 1000 ] ),
			],
			// https://phabricator.wikimedia.org/T218941 : bogus scale
			[
				'{{#coordinates:10|20|scale=boom!}}',
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 1000 ] ),
			],
			// Negative scale
			[
				'{{#coordinates:10|20|scale=-3}}',
				new Coord( 10, 20, Globe::EARTH, [ 'dim' => 1000 ] ),
			]
		];
	}

	public static function provideStrictData() {
		return [
			[
				'{{#coordinates:10|20|globe:Moon dim:10_region:RUS-MOS}}',
				null,
			],
			[
				'{{#coordinates:10|20|globe:Moon dim:10_region:RU-}}',
				null,
			],
			[
				'{{#coordinates:10|20|globe:Moon dim:10|region=RU-longvalue}}',
				null,
			],
			[
				'{{#coordinates:10|20|globe:Moon dim:10_region:РУ-МОС}}',
				null,
			],
		];
	}
}
