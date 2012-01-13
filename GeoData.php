<?php
/**
 * GeoData extension. Initial author Max Semenik
 * License: WTFPL 2.0
 */
$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'GeoData',
	'author' => array( 'Max Semenik' ),
	'url' => 'https://mediawiki.org/wiki/Extension:GeoData',
	'descriptionmsg' => 'geodata-desc',
);

$dir = dirname( __FILE__ );

$wgAutoloadClasses['ApiQueryCoordinates'] = "$dir/ApiQueryCoordinates.php";
$wgAutoloadClasses['ApiQueryGeoSearch'] = "$dir/ApiQueryGeoSearch.php";
$wgAutoloadClasses['Coord'] = "$dir/GeoData.body.php";
$wgAutoloadClasses['CoordinatesParserFunction'] = "$dir/CoordinatesParserFunction.php";
$wgAutoloadClasses['GeoData'] = "$dir/GeoData.body.php";
$wgAutoloadClasses['GeoDataHooks'] = "$dir/GeoDataHooks.php";
$wgAutoloadClasses['GeoMath'] = "$dir/GeoMath.php";
$wgAutoloadClasses['CoordinatesOutput'] = "$dir/CoordinatesParserFunction.php";

$wgExtensionMessagesFiles['GeoData'] = "$dir/GeoData.i18n.php";
$wgExtensionMessagesFiles['GeoDataMagic'] = "$dir/GeoData.i18n.magic.php";

$wgAPIListModules['geosearch'] = 'ApiQueryGeoSearch';
$wgAPIPropModules['coordinates'] = 'ApiQueryCoordinates';

$wgHooks['LoadExtensionSchemaUpdates'][] = 'GeoDataHooks::onLoadExtensionSchemaUpdates';
$wgHooks['ParserFirstCallInit'][] = 'GeoDataHooks::onParserFirstCallInit';
$wgHooks['UnitTestsList'][] = 'GeoDataHooks::onUnitTestsList';
$wgHooks['ArticleDeleteComplete'][] = 'GeoDataHooks::onArticleDeleteComplete';
$wgHooks['LinksUpdate'][] = 'GeoDataHooks::onLinksUpdate';

// =================== start configuration settings ===================

/**
 * Maximum radius for geospatial searches.
 * The greater this variable is, the louder your server ouches.
 */
$wgMaxGeoSearchRadius = 10000; // 10km

/**
 * Default value for the globe (planet/astral body the coordinate is on)
 */
$wgDefaultGlobe = 'earth';

/**
 * Maximum number of coordinates per page, -1 means no limit
 */
$wgMaxCoordinatesPerPage = 500;

/**
 * Conversion table type --> dim
 */
$wgTypeToDim = array(
	'country'        => 1000000,
	'satellite'      => 1000000,
	'state'          => 300000,
	'adm1st'         => 100000,
	'adm2nd'         => 30000,
	'adm3rd'         => 10000,
	'city'           => 10000,
	'isle'           => 10000,
	'mountain'       => 10000,
	'river'          => 10000,
	'waterbody'      => 10000,
	'event'          => 5000,
	'forest'         => 5000,
	'glacier'        => 5000,
	'airport'        => 3000,
	'railwaystation' => 1000,
	'edu'            => 1000,
	'pass'           => 1000,
	'camera'         => 1000,
	'landmark'       => 1000,
);

/**
 * Default value of dim if it is unknown
 */
$wgDefaultDim = 1000;
