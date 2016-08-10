<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'GeoData' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['GeoData'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['GeoDataMagic'] = __DIR__ . '/GeoData.i18n.magic.php';
	/* wfWarn(
		'Deprecated PHP entry point used for GeoData extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	); */
	return;
} else {
	die( 'This version of the GeoData extension requires MediaWiki 1.28+' );
}
