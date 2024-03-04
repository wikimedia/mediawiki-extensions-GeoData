<?php

namespace GeoData\Test;

use GeoData\Coord;
use GeoData\CoordinatesParserFunction;
use GeoData\Globe;
use MediaWikiIntegrationTestCase;
use Parser;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \GeoData\CoordinatesParserFunction
 *
 * @group GeoData
 */
class ParseCoordTest extends MediaWikiIntegrationTestCase {
	/**
	 * @covers \GeoData\CoordinatesParserFunction::parseCoordinates
	 * @dataProvider provideCases
	 */
	public function testParseCoordinates( array $parts, $result, string $globe = 'earth' ) {
		$formatted = '"' . implode( '|', $parts ) . '"';

		/** @var CoordinatesParserFunction $function */
		$function = TestingAccessWrapper::newFromObject( new CoordinatesParserFunction );

		$parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getContentLanguage' ] )
			->getMock();

		$parser->method( 'getContentLanguage' )
			->willReturn( $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' ) );

		$function->parser = $parser;

		$s = $function->parseCoordinates( $parts, new Globe( $globe ) );

		if ( is_string( $result ) ) {
			$this->assertStatusError( $result, $s, "Parsing of $formatted was expected to fail" );
		} else {
			$this->assertStatusGood( $s, "Parsing of $formatted was expected to succeed, but it failed" );
			$val = $s->getValue();
			$this->assertTrue( $val->equalsTo( $result ),
				"Parsing of $formatted was expected to yield something close to"
				. " ({$result->lat}, {$result->lon}), but yielded ({$val->lat}, {$val->lon})"
			);
		}
	}

	public static function provideCases() {
		return [
			// basics
			[ [ 0, 0 ], new Coord( 0, 0 ) ],
			[ [ 75, 25 ], new Coord( 75, 25 ) ],
			[ [ '20.0', '-15.5' ], new Coord( 20, -15.5 ) ],
			[ [ -20, 30, 40, 45 ], new Coord( -20.5, 40.75 ) ],
			[ [ 20, 30, 40, 40, 45, 55 ], new Coord( 20.511111111111, 40.765277777778 ) ],
			// NESW
			[ [ 20.1, 'N', 30, 'E' ], new Coord( 20.1, 30 ) ],
			[ [ 20, 'N', 30.5, 'W' ], new Coord( 20, -30.5 ) ],
			[ [ 20, 'S', 30, 'E' ], new Coord( -20, 30 ) ],
			[ [ 20, 'S', 30, 'W' ], new Coord( -20, -30 ) ],
			[ [ 20, 30, 40, 'S', 40, 45, 55, 'E' ],
				new Coord( -20.511111111111, 40.765277777778 ) ],
			[ [ 20, 30, 40, 'N', 40, 45, 55, 'W' ],
				new Coord( 20.511111111111, -40.765277777778 ) ],
			[ [ 20, 'E', 30, 'W' ], 'geodata-bad-latitude' ],
			[ [ 20, 'S', 30, 'N' ], 'geodata-bad-longitude' ],
			[ [ -20, 'S', 30, 'E' ], 'geodata-bad-latitude' ],
			[ [ 20, 'S', -30, 'W' ], 'geodata-bad-longitude' ],
			// wrong number of parameters
			[ [], 'geodata-bad-input' ],
			[ [ 1 ], 'geodata-bad-input' ],
			[ [ 1, 2, 3 ], 'geodata-bad-input' ],
			[ [ 1, 2, 3, 4, 5 ], 'geodata-bad-input' ],
			[ [ 1, 2, 3, 4, 5, 6, 7 ], 'geodata-bad-input' ],
			[ [ 1, 2, 3, 4, 5, 6, 7, 8, 9 ], 'geodata-bad-input' ],
			[ [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ], 'geodata-bad-input' ],
			// unbalanced NESW
			[ [ 'N', 'E' ], 'geodata-bad-latitude' ],
			[ [ 12, 'N', 'E' ], 'geodata-bad-input' ],
			[ [ 'N', 15, 'E' ], 'geodata-bad-input' ],
			[ [ 1, 2, 3, 'N', 1, 'E' ], 'geodata-bad-longitude' ],
			[ [ 1, 2, 3, 'N', 'E' ], 'geodata-bad-input' ],
			[ [ 1, 2, 3, 'N', 1, 'E' ], 'geodata-bad-longitude' ],
			[ [ 1, 2, 3, 'N', 1, 2, 'E' ], 'geodata-bad-input' ],
			// Fractional numbers inconsistency
			[ [ 1, 2.1, 3, 1, 2, 3 ], 'geodata-bad-latitude' ],
			[ [ 1, 2.1, 3.2, 1, 2, 3 ], 'geodata-bad-latitude' ],
			[ [ 1.00000001, 2.1, 3.2, 1, 2, 3 ], 'geodata-bad-latitude' ],
			[ [ 1.00000001, 2.1, 3, 1, 2, 3 ], 'geodata-bad-latitude' ],
			[ [ 1.00000001, 2, 3, 1, 2, 3 ], 'geodata-bad-latitude' ],
			// only the last component of the coordinate should be non-integer
			[ [ 10.5, 1, 20, 0 ], 'geodata-bad-latitude' ],
			[ [ 10, 30.5, 1, 20, 0, 0 ], 'geodata-bad-latitude' ],
			// Exception per https://phabricator.wikimedia.org/T50488
			[ [ 1.5, 0, 2.5, 0 ], new Coord( 1.5, 2.5 ) ],
			[ [ 1, 2.5, 0, 3, 4.5, 0 ], new Coord( 1.0416666666667, 3.075 ) ],
			// coordinate validation (Earth)
			[ [ -90, 180 ], new Coord( -90, 180 ) ],
			[ [ 90.0000001, -180.00000001 ], 'geodata-bad-latitude' ],
			[ [ 90, 1, 180, 0 ], 'geodata-bad-latitude' ],
			[ [ 10, -1, 20, 0 ], 'geodata-bad-latitude' ],
			[ [ 25, 60, 10, 0 ], 'geodata-bad-latitude' ],
			[ [ 25, 0, 0, 10, 0, 60 ], 'geodata-bad-longitude' ],
			// coordinate validation and normalisation (non-Earth)
			[ [ 10, 20 ], new Coord( 10, 20, 'mars' ), 'mars' ],
			[ [ 110, 20 ], 'geodata-bad-latitude', 'mars' ],
			// Asimov Crater
			[ [ 47, 0, 'S', 355, 3, 'W' ], new Coord( -47, 4.95, 'mars' ), 'mars' ],
			// Quetzalpetlatl Corona
			[ [ 68, 'S', 357, 'E' ], new Coord( -68, 357, 'venus' ), 'venus' ],
		];
	}
}
