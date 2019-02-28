<?php
$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';
$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		$IP . '/extensions/CirrusSearch',
		$IP . '/extensions/Elastica',
	]
);
$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		$IP . '/extensions/CirrusSearch',
		$IP . '/extensions/Elastica',

	]
);

// ParserOutput->geoData
$cfg['suppress_issue_types'][] = 'PhanUndeclaredProperty';
// Interfaces can be caught, we intentionally annotate with the interface
$cfg['suppress_issue_types'][] = 'PhanTypeInvalidThrowsIsInterface';

return $cfg;
