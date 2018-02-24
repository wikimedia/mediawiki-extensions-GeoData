<?php
$cfg = require __DIR__ . '/../../vendor/mediawiki/mediawiki-phan-config/src/config.php';
$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'./../../extensions/CirrusSearch',
		'./../../extensions/Elastica',
	]
);
$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'./../../extensions/CirrusSearch',
		'./../../extensions/Elastica',

	]
);
$cfg['suppress_issue_types'] = [
	'PhanDeprecatedFunction',
	// MYSQLI_TYPE_FLOAT
	'PhanUndeclaredConstant',
	// ParserOutput->geoData
	'PhanUndeclaredProperty',
];
return $cfg;
