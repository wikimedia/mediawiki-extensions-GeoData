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
// MYSQLI_TYPE_FLOAT
$cfg['suppress_issue_types'][] = 'PhanUndeclaredConstant';
// ParserOutput->geoData
$cfg['suppress_issue_types'][] = 'PhanUndeclaredProperty';

return $cfg;
