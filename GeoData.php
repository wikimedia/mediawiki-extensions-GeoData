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

$wgAPIListModules['geosearch'] = 'ApiQueryGeoSearch';
$wgAPIPropModules['coordinates'] = 'ApiQueryCoordinates';

$wgHooks['LoadExtensionSchemaUpdates'][] = 'GeoDataHooks::onLoadExtensionSchemaUpdates';
$wgHooks['ParserFirstCallInit'][] = 'GeoDataHooks::onParserFirstCallInit';
$wgHooks['UnitTestsList'][] = 'GeoDataHooks::onUnitTestsList';
$wgHooks['LanguageGetMagic'][] = 'GeoDataHooks::onLanguageGetMagic';
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
 * Maximum number of coordinates per page
 */
$wgMaxCoordinatesPerPage = 50;
