<?php

namespace GeoData;

use MagicWord;
use MWException;
use Parser;
use ParserOutput;
use PPFrame;
use PPNode;
use Status;

/**
 * Handler for the #coordinates parser function
 */
class CoordinatesParserFunction {
	/**
	 * @var Parser used for processing the current coordinates() call
	 */
	private $parser;

	/**
	 * @var ParserOutput
	 */
	private $output;

	private $named = [],
		$unnamed = [];

	/** @var Globe */
	private $globe;

	/**
	 * #coordinates parser function callback
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param PPNode[] $args
	 * @throws MWException
	 * @return Mixed
	 */
	public function coordinates( $parser, $frame, $args ) {
		$this->parser = $parser;
		$this->output = $parser->getOutput();
		if ( !isset( $this->output->geoData ) ) {
			$this->output->geoData = new CoordinatesOutput();
		}

		$this->unnamed = [];
		$this->named = [];
		$this->parseArgs( $frame, $args );
		$this->processArgs();
		$status = self::parseCoordinates( $this->unnamed, $this->globe );
		if ( $status->isGood() ) {
			$coord = $status->value;
			$status = $this->applyTagArgs( $coord );
			if ( $status->isGood() ) {
				$status = $this->applyCoord( $coord );
				if ( $status->isGood() ) {
					return '';
				}
			}
		}

		$parser->addTrackingCategory( 'geodata-broken-tags-category' );
		$errorText = $this->errorText( $status );
		if ( $errorText === '' ) {
			// Error that doesn't require a message,
			return '';
		}

		return [ "<span class=\"error\">{$errorText}</span>", 'noparse' => false ];
	}

	/**
	 * Parses parser function input
	 * @param PPFrame $frame
	 * @param PPNode[] $args
	 */
	private function parseArgs( $frame, $args ) {
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
	}

	/**
	 * Add an unnamed parameter to the list, turining it into a named one if needed
	 * @param string $value Parameter
	 */
	private function addArg( $value ) {
		$primary = MagicWord::get( 'primary' );
		if ( $primary->match( $value ) ) {
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
	 * @return Status whether save went OK
	 */
	private function applyCoord( Coord $coord ) {
		global $wgMaxCoordinatesPerPage, $wgContLang;

		/** @var CoordinatesOutput $geoData */
		$geoData = $this->output->geoData;
		if ( $wgMaxCoordinatesPerPage >= 0 && $geoData->getCount() >= $wgMaxCoordinatesPerPage ) {
			if ( $geoData->limitExceeded ) {
				return Status::newFatal( '' );
			}
			$geoData->limitExceeded = true;
			return Status::newFatal( 'geodata-limit-exceeded',
				$wgContLang->formatNum( $wgMaxCoordinatesPerPage )
			);
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
	 * Merges parameters with decoded GeoHack data, sets default globe
	 */
	private function processArgs() {
		global $wgDefaultGlobe, $wgContLang;
		// fear not of overwriting the stuff we've just received from the geohack param, it has minimum precedence
		if ( isset( $this->named['geohack'] ) ) {
			$this->named = array_merge( $this->parseGeoHackArgs( $this->named['geohack'] ), $this->named );
		}
		$globe = ( isset( $this->named['globe'] ) && $this->named['globe'] )
			? $wgContLang->lc( $this->named['globe'] )
			: $wgDefaultGlobe;

		$this->globe = new Globe( $globe );

	}

	private function applyTagArgs( Coord $coord ) {
		global $wgTypeToDim, $wgDefaultDim, $wgGeoDataWarningLevel;
		$args = $this->named;
		$coord->primary = isset( $args['primary'] );
		if ( !$this->globe->isKnown() ) {
			switch ( $wgGeoDataWarningLevel['unknown globe'] ) {
				case 'fail':
					return Status::newFatal( 'geodata-bad-globe', $coord->globe );
				case 'warn':
					$this->parser->addTrackingCategory( 'geodata-unknown-globe-category' );
					break;
			}
		}
		$coord->dim = $wgDefaultDim;
		if ( isset( $args['type'] ) ) {
			$coord->type = mb_strtolower( preg_replace( '/\(.*?\).*$/', '', $args['type'] ) );
			if ( isset( $wgTypeToDim[$coord->type] ) ) {
				$coord->dim = $wgTypeToDim[$coord->type];
			} else {
				switch ( $wgGeoDataWarningLevel['unknown type'] ) {
					case 'fail':
						return Status::newFatal( 'geodata-bad-type', $coord->type );
					case 'warn':
						$this->parser->addTrackingCategory( 'geodata-unknown-type-category' );
						break;
				}
			}
		}
		if ( isset( $args['scale'] ) ) {
			$coord->dim = intval( $args['scale'] / 10 );
		}
		if ( isset( $args['dim'] ) ) {
			$dim = $this->parseDim( $args['dim'] );
			if ( $dim !== false ) {
				$coord->dim = intval( $dim );
			}
		}
		$coord->name = isset( $args['name'] ) ? $args['name'] : null;
		if ( isset( $args['region'] ) ) {
			$code = strtoupper( $args['region'] );
			if ( preg_match( '/^([A-Z]{2})(?:-([A-Z0-9]{1,3}))?$/', $code, $m ) ) {
				$coord->country = $m[1];
				$coord->region = isset( $m[2] ) ? $m[2] : null;
			} else {
				if ( $wgGeoDataWarningLevel['invalid region'] == 'fail' ) {
					return Status::newFatal( 'geodata-bad-region', $args['region'] );
				} elseif ( $wgGeoDataWarningLevel['invalid region'] == 'warn' ) {
					$this->parser->addTrackingCategory( 'geodata-unknown-region-category' );
				}
			}
		}
		return Status::newGood();
	}

	private function parseGeoHackArgs( $str ) {
		$result = [];
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
			return $str > 0 ? $str : false;
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
	 * Returns wikitext of status error message in content language
	 *
	 * @param Status $s
	 * @return string
	 */
	private function errorText( Status $s ) {
		$errors = array_merge( $s->getErrorsArray(), $s->getWarningsArray() );
		if ( !count( $errors ) ) {
			return '';
		}
		$err = $errors[0];
		$message = array_shift( $err );
		if ( $message === '' ) {
			return '';
		}
		return wfMessage( $message )->params( $err )->inContentLanguage()->plain();
	}

	/**
	 * Parses coordinates
	 * See https://en.wikipedia.org/wiki/Template:Coord for sample inputs
	 *
	 * @param array $parts Array of coordinate components
	 * @param Globe $globe Globe these coordinates belong to
	 * @return Status Operation status, in case of success its value is a Coord object
	 */
	public static function parseCoordinates( $parts, Globe $globe ) {
		$latSuffixes = [ 'N' => 1, 'S' => -1 ];
		$lonSuffixes = [ 'E' => $globe->getEastSign(), 'W' => -$globe->getEastSign() ];

		$count = count( $parts );
		if ( !is_array( $parts ) || $count < 2 || $count > 8 || ( $count % 2 ) ) {
			return Status::newFatal( 'geodata-bad-input' );
		}
		list( $latArr, $lonArr ) = array_chunk( $parts, $count / 2 );

		$lat = self::parseOneCoord( $latArr, -90, 90, $latSuffixes );
		if ( $lat === false ) {
			return Status::newFatal( 'geodata-bad-latitude' );
		}

		$lon = self::parseOneCoord( $lonArr,
			$globe->getMinLongitude(),
			$globe->getMaxLongitude(),
			$lonSuffixes
		);
		if ( $lon === false ) {
			return Status::newFatal( 'geodata-bad-longitude' );
		}
		return Status::newGood( new Coord( (float)$lat, (float)$lon, $globe->getName() ) );
	}

	private static function parseOneCoord( $parts, $min, $max, $suffixes ) {
		global $wgContLang;

		$count = count( $parts );
		$multiplier = 1;
		$value = 0;
		$alreadyFractional = false;

		$currentMin = $min;
		$currentMax = $max;

		for ( $i = 0; $i < $count; $i++ ) {
			$part = $parts[$i];
			if ( $i > 0 && $i == $count - 1 ) {
				$suffix = self::parseSuffix( $part, $suffixes );
				if ( $suffix ) {
					if ( $value < 0 ) {
						return false; // "-60°S sounds weird, isn't it?
					}
					$value *= $suffix;
					break;
				} elseif ( $i == 3 ) {
					return false;
				}
			}
			// 20° 15.5' 20" is wrong
			if ( $alreadyFractional && $part ) {
				return false;
			}
			if ( !is_numeric( $part ) ) {
				$part = $wgContLang->parseFormattedNumber( $part );
			}

			if ( !is_numeric( $part )
				 || $part < $currentMin
				 || $part > $currentMax ) {
				return false;
			}
			// Use these limits in the next iteration
			$currentMin = 0;
			$currentMax = 59.99999999;

			$alreadyFractional = $part != intval( $part );
			$value += $part * $multiplier * Math::sign( $value );
			$multiplier /= 60;
		}
		if ( $min == 0 && $value < 0 ) {
			$value = $max + $value;
		}
		if ( $value < $min || $value > $max ) {
			return false;
		}
		return $value;
	}

	/**
	 * Parses coordinate suffix such as N, S, E or W
	 *
	 * @param string $str String to test
	 * @param array $suffixes
	 * @return int Sign modifier or 0 if not a suffix
	 */
	private static function parseSuffix( $str, array $suffixes ) {
		global $wgContLang;
		$str = $wgContLang->uc( trim( $str ) );
		return isset( $suffixes[$str] ) ? $suffixes[$str] : 0;
	}
}
