<?php


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

	public function __construct( Parser $parser ) {
		$this->parser = $parser;
		$this->info = GeoData::getCoordInfo();
	}

	/**
	 * Handler for the #coordinates parser function
	 * 
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param Array $args
	 * @return Mixed
	 */
	public function coordinates( $parser, $frame, $args ) {
		$this->output = $parser->getOutput();
		$this->prepareOutput();

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
	 * Make sure that parser output has our storage array
	 */
	private function prepareOutput() {
		if ( !isset( $this->output->geoData ) ) {
			$this->output->geoData = array(
				'primary' => false,
				'secondary' => array(),
				'limitExceeded' => false,
			);
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
		$output = $this->output;
		$count = count( $output->geoData['secondary'] ) + ( $output->geoData['primary'] ? 1 : 0 );
		if ( $count >= $wgMaxCoordinatesPerPage ) {
			if ( $output->geoData['limitExceeded'] ) {
				return Status::newFatal( '' );
			}
			$output->geoData['limitExceeded'] = true;
			return Status::newFatal( 'geodata-limit-exceeded' );
		}
		if ( $coord->primary ) {
			if ( $output->geoData['primary'] ) {
				$output->geoData['secondary'][] = $coord;
				return Status::newFatal( 'geodata-multiple-primary' );
			} else {
				$output->geoData['primary'] = $coord;
			}
		} else {
			$output->geoData['secondary'][] = $coord;
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
		$coord->dim = isset( $args['dim'] ) && is_numeric( $args['dim'] ) && $args['dim'] > 0
				? $args['dim']
				: null;
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
