<?php

namespace GeoData;

use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

/**
 * Schema hook handlers
 */
class SchemaHooks implements LoadExtensionSchemaUpdatesHook {

	/**
	 * LoadExtensionSchemaUpdates hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
	 *
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$base = __DIR__ . '/../sql';
		$dbType = $updater->getDB()->getType();
		$updater->addExtensionTable( 'geo_tags', "$base/$dbType/tables-generated.sql" );
		if ( $dbType !== 'postgres' ) {
			$updater->addExtensionField( 'geo_tags', 'gt_lon_int', "$base/patch-geo_tags-add-lat_int-lon_int.sql" );
		}
	}

}
