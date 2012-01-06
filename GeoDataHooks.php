<?php

class GeoDataHooks {
	/**
	 * LoadExtensionSchemaUpdates hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		if ( $updater->getDB()->getType() != 'mysql' ) {
			throw new MWException( 'GeoData extension currently supports only MySQL' );
		}
		$updater->addExtensionTable( 'geo_tags', dirname( __FILE__ ) . '/GeoData.sql' );
		return true;
	}

	/**
	 * UnitTestsList hook handler
	 * @see: https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 * @param Array $files 
	 */
	public static function onUnitTestsList( &$files ) {
		$dir = dirname( __FILE__ ) . "/tests";
		$files[] = "$dir/ParseCoordTest.php";
		$files[] = "$dir/GeoMathTest.php";
		$files[] = "$dir/TagTest.php";
		return true;
	}

	/**
	 * ParserFirstCallInit hook handler
	 * @see: https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 * @param Parser $parser 
	 */
	public static function onParserFirstCallInit( &$parser ) {
		$parser->setFunctionHook( 'coordinates', 'GeoDataHooks::coordinateHandler', SFH_OBJECT_ARGS );
		return true;
	}

	/**
	 * LanguageGetMagic hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LanguageGetMagic
	 * @param Array $magicWords
	 * @param String $langCode
	 */
	public static function onLanguageGetMagic( &$magicWords, $langCode ) {
		$magicWords['coordinates'] = array( 0, 'coordinates' );
		return true;
	}

	/**
	 * Handler for the #coordinates parser function
	 * 
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param Array $args
	 * @return Mixed
	 */
	public static function coordinateHandler( $parser, $frame, $args ) {
		$output = $parser->getOutput();
		self::prepareOutput( $output );
		$info = GeoData::getCoordInfo();
		$primary = $info['primary'];

		$unnamed = array();
		$named = array();
		$first = trim( $frame->expand( array_shift( $args ) ) );
		if ( $first !== '' ) {
			$unnamed[] = $first;
		}
		foreach ( $args as $arg ) {
			$bits = $arg->splitArg();
			$value = trim( $frame->expand( $bits['value'] ) );
			if ( $bits['index'] === '' ) {
				$named[trim( $frame->expand( $bits['name'] ) )] = $value;
			} elseif ( isset( $primary[$value] ) ) {
				$named['primary'] = true;
			} elseif ( preg_match( '/\S+?:\S*?([ _]+\S+?:\S*?)*/', $value ) ) {
				$named['geohack'] = $value;
			} else {
				$unnamed[] = $value;
			}
		}
		$status = GeoData::parseCoordinates( $unnamed );
		if ( $status->isGood() ) {
			$coord = $status->value;
			$status = GeoData::parseTagArgs( $coord, $named );
			if ( $status->isGood() ) {
				$status = self::applyCoord( $output, $coord );
				if ( $status->isGood() ) {
					return '';
				}
			}
		}
		// Apply tracking category
		if ( !$output->geoData['failures'] ) {
			$output->geoData['failures'] = true;
			$output->addCategory(
				wfMessage( 'geodata-broken-tags-category' )->inContentLanguage()->text(),
				$parser->getTitle()->getText()
			);
		}
		$errorText = $status->getWikiText();
		if ( $errorText == '&lt;&gt;' ) {
			// Error condition that doesn't require a message,
			// can't think of a better way to pass this condition
			return '';
		}
		return array( "<span class=\"error\">{$errorText}</span>", 'noparse' => false );
	}

	/**
	 * Make sure that parser output has our storage array
	 * @param ParserOutput $output
	 */
	private static function prepareOutput( ParserOutput $output ) {
		if ( !isset( $output->geoData ) ) {
			$output->geoData = array(
				'primary' => false,
				'secondary' => array(),
				'failures' => false,
				'limitExceeded' => false,
			);
		}
	}

	/**
	 * Applies a coordinate to parser output
	 *
	 * @param ParserOutput $output
	 * @param Coord $coord
	 * @return Status: whether save went OK
	 */
	private static function applyCoord( ParserOutput $output, Coord $coord ) {
		global $wgMaxCoordinatesPerPage;
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
	 * ArticleDeleteComplete hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 *
	 * @param Article $article
	 * @param User $user
	 * @param String $reason
	 * @param int $id
	 */
	public static function onArticleDeleteComplete( &$article, User &$user, $reason, $id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'geo_tags', array( 'gt_page_id' => $id ), __METHOD__ );
		return true;
	}

	/**
	 * LinksUpdate hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdate
	 * @param LinksUpdate $linksUpdate
	 */
	public static function onLinksUpdate( &$linksUpdate ) {
		global $wgUseDumbLinkUpdate;
		$out = $linksUpdate->getParserOutput();
		$data = array();
		if ( isset( $out->geoData ) ) {
			$geoData = $out->geoData;
			if ( $geoData['primary'] ) {
				$data[] = $geoData['primary'];
			}
			$data = array_merge( $data, $geoData['secondary'] );
		}
		if ( $wgUseDumbLinkUpdate || !count( $data ) ) {
			self::doDumbUpdate( $data, $linksUpdate->mId );
		} else {
			self::doSmartUpdate( $data, $linksUpdate->mId );
		}
		return true;
	}

	private static function doDumbUpdate( $coords, $pageId ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'geo_tags', array( 'gt_page_id' => $pageId ), __METHOD__ );
		$rows = array();
		foreach ( $coords as $coord ) {
			$rows[] = $coord->getRow( $pageId );
		}
		$dbw->insert( 'geo_tags', $rows, __METHOD__ );
	}

	private static function doSmartUpdate( $coords, $pageId ) {
		$prevCoords = GeoData::getAllCoordinates( $pageId, array(), DB_MASTER );
		$add = array();
		$delete = array();
		foreach ( $prevCoords as $old ) {
			$delete[$old->id] = $old;
		}
		foreach ( $coords as $new ) {
			$match = false;
			foreach ( $delete as $id => $old ) {
				if ( $new->fullyEqualsTo( $old ) ) {
					unset( $delete[$id] );
					$match = true;
					break;
				}
			}
			if ( !$match ) {
				$add[] = $new->getRow( $pageId );
			}
		}
		$dbw = wfGetDB( DB_MASTER );
		if ( count( $delete) ) {
			$dbw->delete( 'geo_tags', array( 'gt_id' => array_keys( $delete ) ), __METHOD__ );
		}
		if ( count( $add ) ) {
			$dbw->insert( 'geo_tags', $add, __METHOD__ );
		}
	}
}
