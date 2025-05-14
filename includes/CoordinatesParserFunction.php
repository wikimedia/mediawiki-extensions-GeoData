<?php

namespace GeoData;

use MediaWiki\Config\Config;
use MediaWiki\Language\Language;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Parser\PPFrame;
use MediaWiki\Parser\PPNode;
use StatusValue;

/**
 * Handler for the #coordinates parser function
 */
class CoordinatesParserFunction {
	/**
	 * @var Parser used for processing the current coordinates() call
	 */
	private Parser $parser;
	private ParserOutput $output;

	/** @var (string|true)[] */
	private $named = [];
	/** @var string[] */
	private $unnamed = [];

	/** @var Globe */
	private $globe;

	private Config $config;

	public function __construct(
		Config $config
	) {
		$this->config = $config;
	}

	/**
	 * #coordinates parser function callback
	 *
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param PPNode[] $args
	 * @return string|array
	 */
	public function coordinates( Parser $parser, PPFrame $frame, array $args ) {
		$this->parser = $parser;
		$this->output = $parser->getOutput();

		$this->unnamed = [];
		$this->named = [];
		$this->parseArgs( $frame, $args );
		$this->processArgs();
		$status = $this->parseCoordinates( $this->unnamed, $this->globe );
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
	 * @return Language Current parsing language
	 */
	private function getLanguage(): Language {
		return $this->parser->getContentLanguage();
	}

	/**
	 * Parses parser function input
	 * @param PPFrame $frame
	 * @param PPNode[] $args
	 */
	private function parseArgs( PPFrame $frame, array $args ): void {
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
	 * Add an unnamed parameter to the list, turning it into a named one if needed
	 */
	private function addArg( string $value ): void {
		$primary = $this->parser->getMagicWordFactory()->get( 'primary' );
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
	 * @return StatusValue whether save went OK
	 */
	private function applyCoord( Coord $coord ): StatusValue {
		$maxCoordinatesPerPage = $this->config->get( 'MaxCoordinatesPerPage' );
		$geoData = CoordinatesOutput::getOrBuildFromParserOutput( $this->output );
		if ( $maxCoordinatesPerPage >= 0 && $geoData->getCount() >= $maxCoordinatesPerPage ) {
			if ( $geoData->limitExceeded ) {
				$geoData->setToParserOutput( $this->output );
				return StatusValue::newFatal( '' );
			}
			$geoData->limitExceeded = true;
			$geoData->setToParserOutput( $this->output );
			return StatusValue::newFatal( 'geodata-limit-exceeded',
				$this->getLanguage()->formatNum( $maxCoordinatesPerPage )
			);
		}
		if ( $coord->primary ) {
			if ( $geoData->hasPrimary() ) {
				$geoData->setToParserOutput( $this->output );
				return StatusValue::newFatal( 'geodata-multiple-primary' );
			} else {
				$geoData->addPrimary( $coord );
			}
		} else {
			$geoData->addSecondary( $coord );
		}
		$geoData->setToParserOutput( $this->output );
		return StatusValue::newGood();
	}

	/**
	 * Merges parameters with decoded GeoHack data, sets default globe
	 */
	private function processArgs(): void {
		// fear not of overwriting the stuff we've just received from the geohack param,
		// it has minimum precedence
		if ( isset( $this->named['geohack'] ) ) {
			$this->named = array_merge(
				$this->parseGeoHackArgs( $this->named['geohack'] ), $this->named
			);
		}
		$globe = ( isset( $this->named['globe'] ) && $this->named['globe'] )
			? $this->getLanguage()->lc( $this->named['globe'] )
			: Globe::EARTH;

		$this->globe = new Globe( $globe );
	}

	private function applyTagArgs( Coord $coord ): StatusValue {
		$typeToDim = $this->config->get( 'TypeToDim' );
		$defaultDim = $this->config->get( 'DefaultDim' );
		$geoDataWarningLevel = $this->config->get( 'GeoDataWarningLevel' );

		$args = $this->named;
		$coord->primary = isset( $args['primary'] );
		if ( !$this->globe->isKnown() ) {
			switch ( $geoDataWarningLevel['unknown globe'] ?? null ) {
				case 'fail':
					return StatusValue::newFatal( 'geodata-bad-globe', wfEscapeWikiText( $coord->globe ) );
				case 'warn':
					$this->parser->addTrackingCategory( 'geodata-unknown-globe-category' );
					break;
			}
		}
		$coord->dim = $defaultDim;
		if ( isset( $args['type'] ) ) {
			$coord->type = mb_strtolower( preg_replace( '/\(.*?\).*$/', '', $args['type'] ) );
			if ( isset( $typeToDim[$coord->type] ) ) {
				$coord->dim = $typeToDim[$coord->type];
			} else {
				switch ( $geoDataWarningLevel['unknown type'] ?? null ) {
					case 'fail':
						return StatusValue::newFatal( 'geodata-bad-type', wfEscapeWikiText( $coord->type ) );
					case 'warn':
						$this->parser->addTrackingCategory( 'geodata-unknown-type-category' );
						break;
				}
			}
		}
		if ( isset( $args['scale'] ) && is_numeric( $args['scale'] ) && $args['scale'] > 0 ) {
			$coord->dim = intval( (int)$args['scale'] / 10 );
		}
		if ( isset( $args['dim'] ) ) {
			$dim = $this->parseDim( $args['dim'] );
			if ( $dim !== null ) {
				$coord->dim = $dim;
			}
		}
		$coord->name = $args['name'] ?? null;
		if ( isset( $args['region'] ) ) {
			$code = strtoupper( $args['region'] );
			if ( preg_match( '/^([A-Z]{2})(?:-([A-Z0-9]{1,3}))?$/', $code, $m ) ) {
				$coord->country = $m[1];
				$coord->region = $m[2] ?? null;
			} else {
				switch ( $geoDataWarningLevel['invalid region'] ?? null ) {
					case 'fail':
						return StatusValue::newFatal( 'geodata-bad-region', wfEscapeWikiText( $args['region'] ) );
					case 'warn':
						$this->parser->addTrackingCategory( 'geodata-unknown-region-category' );
						break;
				}
			}
		}
		return StatusValue::newGood();
	}

	/**
	 * @param string $str
	 * @return array<string,string>
	 */
	private function parseGeoHackArgs( string $str ): array {
		$result = [];
		// per GeoHack docs, spaces and underscores are equivalent
		$str = str_replace( '_', ' ', $str );
		foreach ( explode( ' ', $str ) as $arg ) {
			$keyVal = explode( ':', $arg, 2 );
			if ( isset( $keyVal[1] ) ) {
				$result[$keyVal[0]] = $keyVal[1];
			}
		}
		return $result;
	}

	private function parseDim( string $dim ): ?int {
		if ( preg_match( '/^(\d+)(km|m)$/i', $dim, $matches ) ) {
			$dim = (int)$matches[1];
			if ( strtolower( $matches[2] ) === 'km' ) {
				$dim *= 1000;
			}
		}
		return is_numeric( $dim ) && $dim > 0 ? (int)$dim : null;
	}

	/**
	 * Returns wikitext of status error message in content language
	 *
	 * @param StatusValue $status
	 * @return string Wikitext
	 */
	private function errorText( StatusValue $status ): string {
		$errors = $status->getMessages();
		if ( !$errors || !$errors[0]->getKey() ) {
			return '';
		}
		return wfMessage( $errors[0] )->inContentLanguage()->plain();
	}

	/**
	 * Parses coordinates
	 * See https://en.wikipedia.org/wiki/Template:Coord for sample inputs
	 *
	 * @param string[] $parts Array of coordinate components
	 * @param Globe $globe Globe these coordinates belong to
	 * @return StatusValue Operation status, in case of success its value is a Coord object
	 */
	private function parseCoordinates( array $parts, Globe $globe ): StatusValue {
		$latSuffixes = [ 'N' => 1, 'S' => -1 ];
		$lonSuffixes = [ 'E' => $globe->getEastSign(), 'W' => -$globe->getEastSign() ];

		$count = count( $parts );
		if ( $count < 2 || $count > 8 || ( $count % 2 ) ) {
			return StatusValue::newFatal( 'geodata-bad-input' );
		}
		[ $latArr, $lonArr ] = array_chunk( $parts, $count / 2 );

		$lat = $this->parseOneCoord( $latArr, -90, 90, $latSuffixes );
		if ( $lat === false ) {
			return StatusValue::newFatal( 'geodata-bad-latitude' );
		}

		$lon = $this->parseOneCoord( $lonArr,
			$globe->getMinLongitude(),
			$globe->getMaxLongitude(),
			$lonSuffixes
		);
		if ( $lon === false ) {
			return StatusValue::newFatal( 'geodata-bad-longitude' );
		}
		return StatusValue::newGood( new Coord( (float)$lat, (float)$lon, $globe ) );
	}

	/**
	 * @param string[] $parts
	 * @param float $min
	 * @param float $max
	 * @param array<string,int> $suffixes
	 * @return float|false
	 */
	private function parseOneCoord( $parts, $min, $max, array $suffixes ) {
		$count = count( $parts );
		$multiplier = 1;
		$value = 0;
		$alreadyFractional = false;

		$currentMin = $min;
		$currentMax = $max;

		$language = $this->getLanguage();
		for ( $i = 0; $i < $count; $i++ ) {
			$part = $parts[$i];
			if ( $i > 0 && $i == $count - 1 ) {
				$suffix = self::parseSuffix( $part, $suffixes );
				if ( $suffix ) {
					if ( $value < 0 ) {
						// "-60°S sounds weird, doesn't it?
						return false;
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
				$part = $language->parseFormattedNumber( $part );
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
			$value += (float)$part * $multiplier * Math::sign( $value );
			$multiplier /= 60;
		}
		if ( $min == 0 && $value < 0 ) {
			$value += $max;
		}
		if ( $value < $min || $value > $max ) {
			return false;
		}
		return (float)$value;
	}

	/**
	 * Parses coordinate suffix such as N, S, E or W
	 *
	 * @param string $str String to test
	 * @param array<string,int> $suffixes
	 * @return int Sign modifier or 0 if not a suffix
	 */
	private function parseSuffix( string $str, array $suffixes ): int {
		$str = $this->getLanguage()->uc( trim( $str ) );
		return $suffixes[$str] ?? 0;
	}
}
