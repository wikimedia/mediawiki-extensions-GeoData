<?php

class GeoDataHooks {
	/**
	 * LoadExtensionSchemaUpdates hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @param DatabaseUpdater $updater
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		switch ( $updater->getDB()->getType() ) {
			case 'mysql':
			case 'sqlite':
				$updater->addExtensionTable( 'geo_tags', dirname( __FILE__ ) . '/GeoData.sql' );
				break;
			default:
				throw new MWException( 'GeoData extension currently supports only MySQL and SQLite' );
		}
		return true;
	}

	/**
	 * UnitTestsList hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnitTestsList
	 *
	 * @param Array $files
	 * @return bool
	 */
	public static function onUnitTestsList( &$files ) {
		$dir = dirname( __FILE__ ) . "/tests";
		$files[] = "$dir/ParseCoordTest.php";
		$files[] = "$dir/GeoMathTest.php";
		$files[] = "$dir/TagTest.php";
		$files[] = "$dir/MiscGeoDataTest.php";
		return true;
	}

	/**
	 * ParserFirstCallInit hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 *
	 * @param Parser $parser
	 * @return bool
	 */
	public static function onParserFirstCallInit( &$parser ) {
		$parser->setFunctionHook( 'coordinates', 
			array( new CoordinatesParserFunction( $parser ), 'coordinates' ),
			SFH_OBJECT_ARGS
		);
		return true;
	}

	/**
	 * ArticleDeleteComplete hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 *
	 * @param Article $article
	 * @param User $user
	 * @param String $reason
	 * @param int $id
	 * @return bool
	 */
	public static function onArticleDeleteComplete( &$article, User &$user, $reason, $id ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete( 'geo_tags', array( 'gt_page_id' => $id ), __METHOD__ );
		return true;
	}

	/**
	 * LinksUpdate hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdate
	 *
	 * @param LinksUpdate $linksUpdate
	 * @return bool
	 */
	public static function onLinksUpdate( &$linksUpdate ) {
		global $wgUseDumbLinkUpdate;
		$out = $linksUpdate->getParserOutput();
		$data = array();
		$coordFromMetadata = self::getCoordinatesIfFile( $linksUpdate->getTitle() );
		if ( isset( $out->geoData ) ) {
			$geoData = $out->geoData;
			// Use coordinates from file metadata unless overridden on description page
			if ( $coordFromMetadata && !$geoData->getPrimary() ) {
				$geoData->addPrimary( $coordFromMetadata );
			}
			$data = $geoData->getAll();
		} elseif ( $coordFromMetadata ) {
			$data[] = $coordFromMetadata;
		}
		if ( $wgUseDumbLinkUpdate || !count( $data ) ) {
			self::doDumbUpdate( $data, $linksUpdate->mId );
		} else {
			self::doSmartUpdate( $data, $linksUpdate->mId );
		}
		return true;
	}

	private static function getCoordinatesIfFile( Title $title ) {
		if ( $title->getNamespace() != NS_FILE ) {
			return null;
		}
		$file = wfFindFile( $title );
		if ( !$file ) {
			return null;
		}
		$metadata = $file->getMetadata();
		wfSuppressWarnings();
		$metadata = unserialize( $metadata );
		wfRestoreWarnings();
		if ( isset( $metadata ) && isset( $metadata['GPSLatitude'] ) && isset( $metadata['GPSLongitude'] ) ) {
			$lat = $metadata['GPSLatitude'];
			$lon = $metadata['GPSLongitude'];
			$refs = self::decodeRefs( $metadata );
			$lat *= $refs[0];
			$lon *= $refs[1];
			if ( GeoData::validateCoord( $lat, $lon, 'earth' ) ) {
				$coord = new Coord( $lat, $lon );
				$coord->primary = true;
				return $coord;
			}
		}
		return null;
	}

	private static function decodeRefs( $metadata ) {
		global $wgGlobes;
		if ( isset( $metadata['GPSLatitudeRef'] ) && isset( $metadata['GPSLongitudeRef'] ) ) {
			$coordInfo = GeoData::getCoordInfo();
			$latRef = GeoData::parseSuffix( $metadata['GPSLatitudeRef'], $coordInfo['lat'] );
			$lonRef = GeoData::parseSuffix( $metadata['GPSLongitudeRef'], $wgGlobes['earth'] );
			if ( $latRef != 0 && $lonRef != 0 ) {
				return array( $latRef, $lonRef );
			}
		}
		return array( 1, 1 );
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

	/**
	 * FileUpload hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
	 *
	 * @param LocalFile $file
	 * @return bool
	 */
	public static function onFileUpload( LocalFile $file ) {
		$wp = WikiPage::factory( $file->getTitle() );
		$po = new ParserOptions();
		$pout = $wp->getParserOutput( $po );
		$lu = new LinksUpdate( $file->getTitle(), $pout );
		self::onLinksUpdate( $lu );
		return true;
	}
}
