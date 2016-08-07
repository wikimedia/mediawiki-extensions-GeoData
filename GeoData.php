<?php
/**
 * GeoData extension. Initial author Max Semenik
 * License: WTFPL 2.0
 */
$wgExtensionCredits['other'][] = [
	'path' => __FILE__,
	'name' => 'GeoData',
	'author' => [ 'Max Semenik' ],
	'url' => 'https://www.mediawiki.org/wiki/Extension:GeoData',
	'descriptionmsg' => 'geodata-desc',
	'license-name' => 'WTFPL',
];

$dir = __DIR__;

$wgAutoloadClasses['GeoData\ApiQueryCoordinates'] = "$dir/includes/api/ApiQueryCoordinates.php";
$wgAutoloadClasses['GeoData\ApiQueryGeoSearch'] = "$dir/includes/api/ApiQueryGeoSearch.php";
$wgAutoloadClasses['GeoData\ApiQueryGeoSearchDb'] = "$dir/includes/api/ApiQueryGeoSearchDb.php";
$wgAutoloadClasses['GeoData\ApiQueryGeoSearchElastic'] = "$dir/includes/api/ApiQueryGeoSearchElastic.php";

$wgAutoloadClasses['Coord'] = "$dir/includes/BC.php";
$wgAutoloadClasses['CoordinatesOutput'] = "$dir/includes/BC.php";
$wgAutoloadClasses['GeoData\BoundingBox'] = "$dir/includes/BoundingBox.php";
$wgAutoloadClasses['GeoData\Coord'] = "$dir/includes/Coord.php";
$wgAutoloadClasses['GeoData\CoordinatesOutput'] = "$dir/includes/CoordinatesOutput.php";
$wgAutoloadClasses['GeoData\CoordinatesParserFunction'] = "$dir/includes/CoordinatesParserFunction.php";
$wgAutoloadClasses['GeoData\GeoData'] = "$dir/includes/GeoData.body.php";
$wgAutoloadClasses['GeoData\Globe'] = "$dir/includes/Globe.php";
$wgAutoloadClasses['GeoData\Hooks'] = "$dir/includes/Hooks.php";
$wgAutoloadClasses['GeoData\Math'] = "$dir/includes/Math.php";
$wgAutoloadClasses['GeoData\Searcher'] = "$dir/includes/Searcher.php";

$wgMessagesDirs['GeoData'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['GeoData'] = "$dir/GeoData.i18n.php";
$wgExtensionMessagesFiles['GeoDataMagic'] = "$dir/GeoData.i18n.magic.php";

$wgAPIPropModules['coordinates'] = 'GeoData\ApiQueryCoordinates';

$wgHooks['LoadExtensionSchemaUpdates'][] = 'GeoData\Hooks::onLoadExtensionSchemaUpdates';
$wgHooks['ParserFirstCallInit'][] = 'GeoData\Hooks::onParserFirstCallInit';
$wgHooks['UnitTestsList'][] = 'GeoData\Hooks::onUnitTestsList';
$wgHooks['ArticleDeleteComplete'][] = 'GeoData\Hooks::onArticleDeleteComplete';
$wgHooks['LinksUpdate'][] = 'GeoData\Hooks::onLinksUpdate';
$wgHooks['FileUpload'][] = 'GeoData\Hooks::onFileUpload';
$wgHooks['OutputPageParserOutput'][] = 'GeoData\Hooks::onOutputPageParserOutput';
$wgHooks['CirrusSearchMappingConfig'][] = 'GeoData\Hooks::onCirrusSearchMappingConfig';
$wgHooks['CirrusSearchBuildDocumentParse'][] = 'GeoData\Hooks::onCirrusSearchBuildDocumentParse';
$wgHooks['ParserTestTables'][] = 'GeoData\Hooks::onParserTestTables';
$wgHooks['ApiQuery::moduleManager'][] = 'GeoData\Hooks::onApiQueryModuleManager';

// Tracking categories for Special:TrackingCategories
$wgTrackingCategories[] = 'geodata-broken-tags-category';
$wgTrackingCategories[] = 'geodata-unknown-globe-category';
$wgTrackingCategories[] = 'geodata-unknown-region-category';
$wgTrackingCategories[] = 'geodata-unknown-type-category';

// =================== start configuration settings ===================

/**
 * Maximum radius in metres for geospatial searches around a point.
 * For bounding box based searches, the area must not exceed R^2*4.
 *
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
$wgTypeToDim = [
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
];

/**
 * Default value of dim if it is unknown
 */
$wgDefaultDim = 1000;

/**
 * Description of globes. Allows to extend or override the defaults from Globe.php
 */
$wgGlobes = [
	/*
	Example definition:
	'saraksh' => [
		// Range of latitudes
		'lon' => [ -180, 180 ],
		// What sign should N degrees east have?
		'east' => -1,
		// Radius in metres. If omitted, no distance calculation will be possible on this globe
		'radius' => 12345678.9,
	],
	*/
];

/**
 * Controls what GeoData should do when it encounters some problem.
 * Reaction type:
 *  - track - Add tracking category
 *  - fail - Consider the tag invalid, display message and add tracking category
 *  - none - Do nothing
 */
$wgGeoDataWarningLevel = [
	'unknown type' => 'track',
	'unknown globe' => 'none',
	'invalid region' => 'track',
];

/**
 * How many gt_(lat|lon)_int units per degree
 * Run updateIndexGranularity.php after changing this
 */
$wgGeoDataIndexGranularity = 10;

/**
 * Which backend should be used by spatial searhces: 'db' or 'elastic'
 */
$wgGeoDataBackend = 'db';

/**
 * Specifies which information about page's primary coordinate is added to global JS variable wgCoordinates.
 * Setting it to false or empty array will disable wgCoordinates.
 */
$wgGeoDataInJS = [ 'lat', 'lon' ];

/**
 * Enables the use of GeoData as a CirrusSearch plugin for indexing.
 * This is separate from $wgGeoDataBackend: you could be filling Elasticsearch index and using old search
 * meanwhile. However, if backend is already set to 'elastic', GeoData always behaves as if it's true
 */
$wgGeoDataUseCirrusSearch = false;

/**
 * If set to true, will add debug information to API output
 */
$wgGeoDataDebug = false;
