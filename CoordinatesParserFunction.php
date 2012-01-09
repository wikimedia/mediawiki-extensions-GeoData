<?php


/**
 * Handler for the #coordinates parser function
 */
class CoordinatesParserFunction {
	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var ParserOutput
	 */
	private $output;

	private $named = array(),
		$unnamed = array(),
		$info;

	/**
	 * Constructor
	 * @param Parser $parser: Parser object to associate with
	 */
	public function __construct( Parser $parser ) {
		$this->parser = $parser;
		$this->info = GeoData::getCoordInfo();
	}

	/**
	 * #coordinates parser function callback
	 * 
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param Array $args
	 * @return Mixed
	 */
	public function coordinates( $parser, $frame, $args ) {
		if ( $parser != $this->parser ) {
			throw new MWException( __METHOD__ . '() called by wrong parser' );
		}
		$this->output = $parser->getOutput();
		if ( !isset( $this->output->geoData ) ) {
			$this->output->geoData = new CoordinatesOutput();
		}

		$this->unnamed = array();
		$this->named = array();
		$first = trim( $frame->expand( array_shift( $args ) ) );
		$this->addArg( $first );
		foreach ( $args as $arg ) {
			$bits = $arg->splitArg();
			$value = trim( $frame->expand( $bits['value'] ) );
			if ( $bits['index'] === '' ) {
				$this->named[trim( $frame->expand( $bits['name'] ) )] = $value;
			} else {
				$this->addArg( $value );
			}
		}
		$status = GeoData::parseCoordinates( $this->unnamed );
		if ( $status->isGood() ) {
			$coord = $status->value;
			$status = $this->parseTagArgs( $coord );
			if ( $status->isGood() ) {
				$status = $this->applyCoord( $coord );
				if ( $status->isGood() ) {
					return '';
				}
			}
		}

		$this->addCategory( wfMessage( 'geodata-broken-tags-category' ) );
		$errorText = $status->getWikiText();
		if ( $errorText == '&lt;&gt;' ) {
			// Error that doesn't require a message,
			// can't think of a better way to pass this condition
			return '';
		}
		return array( "<span class=\"error\">{$errorText}</span>", 'noparse' => false );
	}

	/**
	 * Add an unnamed parameter to the list, turining it into a named one if needed
	 * @param String $value: Parameter
	 */
	private function addArg( $value ) {
		if ( isset( $this->info['primary'][$value] ) ) {
			$this->named['primary'] = true;
		} elseif ( preg_match( '/\S+?:\S*?([ _]+\S+?:\S*?)*/', $value ) ) {
			$this->named['geohack'] = $value;
		} elseif ( $value != '' ) {
			$this->unnamed[] = $value;
		}
	}

	/**
	 * Applies a coordinate to parser output
	 *
	 * @param Coord $coord
	 * @return Status: whether save went OK
	 */
	private function applyCoord( Coord $coord ) {
		global $wgMaxCoordinatesPerPage;
		$geoData = $this->output->geoData;
		if ( $geoData->getCount() >= $wgMaxCoordinatesPerPage ) {
			if ( $geoData->limitExceeded ) {
				return Status::newFatal( '' );
			}
			$geoData->limitExceeded = true;
			return Status::newFatal( 'geodata-limit-exceeded' );
		}
		if ( $coord->primary ) {
			if ( $geoData->getPrimary() ) {
				return Status::newFatal( 'geodata-multiple-primary' );
			} else {
				$geoData->addPrimary( $coord );
			}
		} else {
			$geoData->addSecondary( $coord );
		}
		return Status::newGood();
	}

	/**
	 *
	 * @param Coord $coord
	 */
	private function parseTagArgs( Coord $coord ) {
		global $wgDefaultGlobe, $wgContLang;
		$result = Status::newGood();
		$args = $this->named;
		// fear not of overwriting the stuff we've just received from the geohack param, it has minimum precedence
		if ( isset( $args['geohack'] ) ) {
			$args = array_merge( $this->parseGeoHackArgs( $args['geohack'] ), $args );
		}
		$coord->primary = isset( $args['primary'] );
		$coord->globe = isset( $args['globe'] ) ? $wgContLang->lc( $args['globe'] ) : $wgDefaultGlobe;
		if ( isset( $args['dim'] ) ) {
			$dim = $this->parseDim( $args['dim'] );
			if ( $dim !== '' ) {
				$coord->dim = $dim;
			}
		}
		$coord->type = isset( $args['type'] ) ? $args['type'] : null;
		$coord->name = isset( $args['name'] ) ? $args['name'] : null;
		if ( isset( $args['region'] ) ) {
			$code = strtoupper( $args['region'] );
			if ( preg_match( '/([A-Z]{2})(?:-([A-Z0-9]{1,3}))/', $code, $m ) ) {
				$coord->country = $m[1];
				$coord->region = $m[2];
			} else {
				$result->warning( 'geodata-bad-region', $args['region'] ); //@todo: actually use this warning
			}
		}
		return $result;
	}

	private function parseGeoHackArgs( $str ) {
		$result = array();
		$str = str_replace( '_', ' ', $str ); // per GeoHack docs, spaces and underscores are equivalent
		$parts = explode( ' ', $str );
		foreach ( $parts as $arg ) {
			$keyVal = explode( ':', $arg, 2 );
			if ( count( $keyVal ) != 2 ) {
				continue;
			}
			$result[$keyVal[0]] = $keyVal[1];
		}
		return $result;
	}

	private function parseDim( $str ) {
		if ( is_numeric( $str ) ) {
			return $str > 0;
		}
		if ( !preg_match( '/^(\d+)(km|m)$/i', $str, $m ) ) {
			return false;
		}
		if ( strtolower( $m[2] ) == 'km' ) {
			return $m[1] * 1000;
		}
		return $m[1];
	}

	/**
	 * Adds a category to the output
	 *
	 * @param String|Message $name: Category name
	 */
	private function addCategory( $name ) {
		if ( $name instanceof Message ) {
			$name = $name->inContentLanguage()->text();
		}
		$this->output->addCategory( $name, $this->parser->getTitle()->getText() );
	}
}

class CoordinatesOutput {
	public $limitExceeded = false;
	private $primary = false,
		$secondary = array();

	public function getCount() {
		return count( $this->secondary ) + ( $this->primary ? 1 : 0 );
	}

	public function addPrimary( Coord $c ) {
		if ( $this->primary ) {
			throw new MWException( 'Attempted to insert second primary function into ' . __CLASS__ );
		}
		$this->primary = $c;
	}

	public function addSecondary( Coord $c ) {
		$this->secondary[] = $c;
	}

	public function getPrimary() {
		return $this->primary;
	}

	public function getSecondary() {
		return $this->secondary;
	}

	public function getAll() {
		$res = $this->secondary;
		if ( $this->primary ) {
			array_unshift( $res, $this->primary );
		}
		return $res;
	}
}