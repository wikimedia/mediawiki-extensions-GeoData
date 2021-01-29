<?php

namespace MediaWiki\Tests\GeoData;

use GeoData\Coord;
use GeoData\CoordinatesOutput;
use MediaWikiUnitTestCase;
use ParserOutput;

class CoordinatesOutputTest extends MediaWikiUnitTestCase {

	public function provideCoordOutputs() {
		yield 'empty' => [ new CoordinatesOutput() ];
		$limited = new CoordinatesOutput();
		$limited->limitExceeded = true;
		yield 'limit exceeded' => [ $limited ];
		$withPrimary = new CoordinatesOutput();
		$withPrimary->addPrimary( new Coord( 1.1, 1.2, 'lalala', [ 'dim' => 42 ] ) );
		yield 'with primary' => [ $withPrimary ];
		$withSecondary = new CoordinatesOutput();
		$withSecondary->addSecondary( new Coord( 1.1, 1.2, 'lalala', [ 'dim' => 42 ] ) );
		$withSecondary->addSecondary( new Coord( 1.3, 1.4, 'trulala', [ 'dim' => 24 ] ) );
		yield 'with secondary' => [ $withSecondary ];
	}

	/**
	 * @dataProvider provideCoordOutputs
	 * @covers \GeoData\CoordinatesOutput::jsonSerialize
	 * @covers \GeoData\CoordinatesOutput::newFromJson
	 * @param CoordinatesOutput $output
	 */
	public function testSerializeDeserialize( CoordinatesOutput $output ) {
		$deserialized = CoordinatesOutput::newFromJson( $output->jsonSerialize() );
		$this->assertSameCoordOutputs( $output, $deserialized );
	}

	/**
	 * @dataProvider provideCoordOutputs
	 * @covers \GeoData\CoordinatesOutput::getFromParserOutput
	 * @covers \GeoData\CoordinatesOutput::setToParserOutput
	 */
	public function testParserOutput( CoordinatesOutput $coordinatesOutput ) {
		$parserOutput = new ParserOutput();
		$coordinatesOutput->setToParserOutput( $parserOutput );
		$retrieved = CoordinatesOutput::getFromParserOutput( $parserOutput );
		$this->assertSameCoordOutputs( $coordinatesOutput, $retrieved );
	}

	/**
	 * @dataProvider provideCoordOutputs
	 * @covers \GeoData\CoordinatesOutput::getFromParserOutput
	 * @covers \GeoData\CoordinatesOutput::setToParserOutput
	 */
	public function testParserOutputForwardCompat( CoordinatesOutput $coordinatesOutput ) {
		$parserOutput = new ParserOutput();
		$parserOutput->setExtensionData(
			CoordinatesOutput::GEO_DATA_COORDS_OUTPUT,
			$coordinatesOutput->jsonSerialize()
		);
		$this->assertSameCoordOutputs( $coordinatesOutput, CoordinatesOutput::getFromParserOutput( $parserOutput ) );
	}

	/**
	 * @dataProvider provideCoordOutputs
	 * @covers \GeoData\CoordinatesOutput::getFromParserOutput
	 * @covers \GeoData\CoordinatesOutput::setToParserOutput
	 */
	public function testParserOutputBackwardCompat( CoordinatesOutput $coordinatesOutput ) {
		$parserOutput = new ParserOutput();
		$parserOutput->setExtensionData(
			CoordinatesOutput::GEO_DATA_COORDS_OUTPUT,
			$coordinatesOutput
		);
		$this->assertSameCoordOutputs( $coordinatesOutput, CoordinatesOutput::getFromParserOutput( $parserOutput ) );
	}

	private function assertSameCoordOutputs( CoordinatesOutput $expected, CoordinatesOutput $actual ) {
		$this->assertSame( $expected->limitExceeded, $actual->limitExceeded );
		if ( $expected->getPrimary() ) {
			$this->assertTrue( $expected->getPrimary()->fullyEqualsTo( $actual->getPrimary() ) );
		} else {
			$this->assertFalse( $actual->getPrimary() );
		}
		// phpcs:ignore MediaWiki.PHPUnit.AssertCount.NotUsed
		$this->assertSame( count( $expected->getSecondary() ), count( $actual->getSecondary() ) );
		for ( $i = 0; $i < count( $expected->getSecondary() ); $i++ ) {
			$this->assertTrue( $expected->getSecondary()[$i]->fullyEqualsTo( $actual->getSecondary()[$i] ) );
		}
	}
}
