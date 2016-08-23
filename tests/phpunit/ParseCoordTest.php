<?php

namespace GeoData\Test;

use GeoData\Coord;
use GeoData\CoordinatesParserFunction;
use GeoData\Globe;
use MediaWikiTestCase;

/**
 * @group GeoData
 */
class ParseCoordTest extends MediaWikiTestCase {
	/**
	 * @dataProvider getCases
	 */
	public function testParseCoordinates( $parts, $result, $globe = 'earth' ) {
		$formatted = '"' . implode( $parts, '|' ) . '"';
		$s = CoordinatesParserFunction::parseCoordinates( $parts, new Globe( $globe ) );
		$val = $s->value;
		if ( $result === false ) {
			$this->assertFalse( $s->isGood(), "Parsing of $formatted was expected to fail" );
		} else {
			$msg = $s->isGood() ? '' : $s->getWikiText();
			$this->assertTrue( $s->isGood(), "Parsing of $formatted was expected to succeed, but it failed: $msg" );
			$this->assertTrue( $val->equalsTo( $result ),
				"Parsing of $formatted was expected to yield something close to"
				. " ({$result->lat}, {$result->lon}), but yielded ({$val->lat}, {$val->lon})"
			);
		}
	}

	public function getCases() {
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
			[ [ 20, 30, 40, 'S', 40, 45, 55, 'E' ], new Coord( -20.511111111111, 40.765277777778 ) ],
			[ [ 20, 30, 40, 'N', 40, 45, 55, 'W' ], new Coord( 20.511111111111, -40.765277777778 ) ],
			[ [ 20, 'E', 30, 'W' ], false ],
			[ [ 20, 'S', 30, 'N' ], false ],
			[ [ -20, 'S', 30, 'E' ], false ],
			[ [ 20, 'S', -30, 'W' ], false ],
			// wrong number of parameters
			[ [], false ],
			[ [ 1 ], false ],
			[ [ 1, 2, 3 ], false ],
			[ [ 1, 2, 3, 4, 5 ], false ],
			[ [ 1, 2, 3, 4, 5, 6, 7 ], false ],
			[ [ 1, 2, 3, 4, 5, 6, 7, 8, 9 ], false ],
			[ [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ], false ],
			// unbalanced NESW
			[ [ 'N', 'E' ], false ],
			[ [ 12, 'N', 'E' ], false ],
			[ [ 'N', 15, 'E' ], false ],
			[ [ 1, 2, 3, 'N', 1, 'E' ], false ],
			[ [ 1, 2, 3, 'N', 'E' ], false ],
			[ [ 1, 2, 3, 'N', 1, 'E' ], false ],
			[ [ 1, 2, 3, 'N', 1, 2, 'E' ], false ],
			// Fractional numbers inconsistency
			[ [ 1, 2.1, 3, 1, 2, 3 ], false ],
			[ [ 1, 2.1, 3.2, 1, 2, 3 ], false ],
			[ [ 1.00000001, 2.1, 3.2, 1, 2, 3 ], false ],
			[ [ 1.00000001, 2.1, 3, 1, 2, 3 ], false ],
			[ [ 1.00000001, 2, 3, 1, 2, 3 ], false ],
			// only the last component of the coordinate should be non-integer
			[ [ 10.5, 1, 20, 0 ], false ],
			[ [ 10, 30.5, 1, 20, 0, 0 ], false ],
			// Exception per https://phabricator.wikimedia.org/T50488
			[ [ 1.5, 0, 2.5, 0 ], new Coord( 1.5, 2.5 ) ],
			[ [ 1, 2.5, 0, 3, 4.5, 0 ], new Coord( 1.0416666666667, 3.075 ) ],
			// coordinate validation (Earth)
			[ [ -90, 180 ], new Coord( -90, 180 ) ],
			[ [ 90.0000001, -180.00000001 ], false ],
			[ [ 90, 1, 180, 0 ], false ],
			[ [ 10, -1, 20, 0 ], false ],
			[ [ 25, 60, 10, 0 ], false ],
			[ [ 25, 0, 0, 10, 0, 60 ], false ],
			// coordinate validation and normalisation (non-Earth)
			[ [ 10, 20 ], new Coord( 10, 20, 'mars' ), 'mars' ],
			[ [ 110, 20 ], false, 'mars' ],
			[ [ 47, 0, 'S', 355, 3, 'W' ], new Coord( -47, 4.95, 'mars' ), 'mars' ], // Asimov Crater
			[ [ 68, 'S', 357, 'E' ], new Coord( -68, 357, 'venus' ), 'venus' ], // Quetzalpetlatl Corona
		];
	}
}