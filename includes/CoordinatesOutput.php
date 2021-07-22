<?php

namespace GeoData;

use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use ParserOutput;
use Wikimedia\Assert\Assert;

/**
 * Class that holds output of a parse opertion
 */
class CoordinatesOutput implements JsonSerializable {
	/**
	 * Key used to store this object in the ParserOutput extension data.
	 * Visible for testing only.
	 */
	public const GEO_DATA_COORDS_OUTPUT = 'GeoDataCoordsOutput';

	/** @var bool */
	public $limitExceeded = false;
	/** @var Coord|false */
	private $primary = false;
	/** @var Coord[] */
	private $secondary = [];

	/**
	 * Fetch the current CoordinatesOutput attached to this ParserOutput
	 * or create a new one.
	 *
	 * @note The changes made to the CoordinatesOutput object are not stored
	 * back into the ParserOutput until self::setToParserOutput is called.
	 *
	 * @see self::setToParserOutput
	 * @param ParserOutput $parserOutput
	 * @return CoordinatesOutput
	 */
	public static function getOrBuildFromParserOutput(
		ParserOutput $parserOutput
	): CoordinatesOutput {
		$coord = self::getFromParserOutput( $parserOutput );
		if ( $coord === null ) {
			$coord = new CoordinatesOutput();
		}
		return $coord;
	}

	/**
	 * Write the coords to ParserOutput object.
	 * @param ParserOutput $parserOutput
	 */
	public function setToParserOutput( ParserOutput $parserOutput ) {
		$parserOutput->setExtensionData( self::GEO_DATA_COORDS_OUTPUT, $this->jsonSerialize() );
	}

	/**
	 * Get the CoordinatesOutput attached to this ParserOutput
	 * @param ParserOutput $parserOutput
	 * @return CoordinatesOutput|null existing CoordinatesOutput or null
	 */
	public static function getFromParserOutput( ParserOutput $parserOutput ) {
		$coordsOutput = $parserOutput->getExtensionData( self::GEO_DATA_COORDS_OUTPUT );
		if ( $coordsOutput !== null ) {
			if ( is_array( $coordsOutput ) ) {
				$coordsOutput = self::newFromJson( $coordsOutput );
			}
			Assert::invariant( $coordsOutput instanceof CoordinatesOutput,
				'ParserOutput extension data ' . self::GEO_DATA_COORDS_OUTPUT .
				' must be an instance of CoordinatesOutput' );
		}
		return $coordsOutput;
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
	 * @return bool Whether this output has primary coordinates
	 */
	public function hasPrimary(): bool {
		return (bool)$this->primary;
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

	/**
	 * @return array
	 */
	public function jsonSerialize() {
		return [
			'limitExceeded' => $this->limitExceeded,
			'primary' => $this->primary ? $this->primary->jsonSerialize() : $this->primary,
			'secondary' => array_map( static function ( Coord $coord ) {
				return $coord->jsonSerialize();
			}, $this->secondary )
		];
	}

	/**
	 * Instantiate a CoordinatesOutput from $json array created with self::jsonSerialize.
	 *
	 * @internal
	 * @param array $jsonArray
	 * @return static
	 * @see self::jsonSerialize
	 */
	public static function newFromJson( array $jsonArray ): self {
		$coordOutput = new CoordinatesOutput();
		$coordOutput->limitExceeded = $jsonArray['limitExceeded'];
		$coordOutput->primary = $jsonArray['primary'] ? Coord::newFromJson( $jsonArray['primary'] ) : false;
		$coordOutput->secondary = array_map( static function ( array $jsonCoord ) {
			return Coord::newFromJson( $jsonCoord );
		}, $jsonArray['secondary'] );
		return $coordOutput;
	}
}
