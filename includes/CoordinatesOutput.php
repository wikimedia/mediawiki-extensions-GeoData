<?php

namespace GeoData;

use InvalidArgumentException;
use LogicException;
use ParserOutput;
use Wikimedia\Assert\Assert;

/**
 * Class that holds output of a parse opertion
 */
class CoordinatesOutput {
	/**
	 * Key used to store this object in the ParserOutput extension data.
	 * Visible for testing only.
	 */
	const GEO_DATA_COORDS_OUTPUT = 'GeoDataCoordsOutput';

	/** @var bool */
	public $limitExceeded = false;
	/** @var Coord|false */
	private $primary = false;
	/** @var Coord[] */
	private $secondary = [];

	/**
	 * Fetch the current CoordinatesOutput attached to this ParserOutput
	 * or create a new one and attach it.
	 * @param ParserOutput $parserOutput
	 * @return CoordinatesOutput
	 */
	public static function getOrBuildFromParserOutput(
		ParserOutput $parserOutput
	): CoordinatesOutput {
		$coord = self::getFromParserOutput( $parserOutput );
		if ( $coord === null ) {
			$coord = new CoordinatesOutput();
			$parserOutput->setExtensionData( self::GEO_DATA_COORDS_OUTPUT, $coord );
		}
		return $coord;
	}

	/**
	 * Get the CoordinatesOutput attached to this ParserOutput
	 * @param ParserOutput $parserOutput
	 * @return CoordinatesOutput|null existing CoordinatesOutput or null
	 */
	public static function getFromParserOutput( ParserOutput $parserOutput ) {
		$coordsOuput = $parserOutput->getExtensionData( self::GEO_DATA_COORDS_OUTPUT );
		if ( $coordsOuput !== null ) {
			Assert::invariant( $coordsOuput instanceof CoordinatesOutput,
				'ParserOutput extension data ' . self::GEO_DATA_COORDS_OUTPUT .
				' must be an instance of CoordinatesOutput' );
		}
		return $coordsOuput;
	}

	/**
	 * @return int
	 */
	public function getCount() {
		return count( $this->secondary ) + ( $this->primary ? 1 : 0 );
	}

	/**
	 * Sets primary coordinates, throwing an exception if already set
	 *
	 * @param Coord $c
	 * @throws LogicException
	 */
	public function addPrimary( Coord $c ) {
		if ( $this->primary ) {
			throw new LogicException( 'Primary coordinates already set' );
		}
		$this->primary = $c;
	}

	/**
	 * @param Coord $c
	 * @throws InvalidArgumentException
	 */
	public function addSecondary( Coord $c ) {
		if ( $c->primary ) {
			throw new InvalidArgumentException( 'Attempt to pass primary coordinates as secondary' );
		}
		$this->secondary[] = $c;
	}

	/**
	 * @return Coord|false
	 */
	public function getPrimary() {
		return $this->primary;
	}

	/**
	 * @return Coord[]
	 */
	public function getSecondary() {
		return $this->secondary;
	}

	/**
	 * @return Coord[]
	 */
	public function getAll() {
		$res = $this->secondary;
		if ( $this->primary ) {
			array_unshift( $res, $this->primary );
		}
		return $res;
	}
}
