<?php
/**
 * Internationalisation file for GeoData extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

$messages['en'] = array(
	'geodata-desc' => 'Adds geographical coordinates storage and retrieval functionality.',
	'geodata-bad-input' => 'Invalid arguments have been passed to the <nowiki>{{#coordinates:}}</nowiki> function',
	'geodata-bad-latitude' => '<nowiki>{{#coordinates:}}</nowiki>: invalid latitude',
	'geodata-bad-longitude' => '<nowiki>{{#coordinates:}}</nowiki>: invalid longitude',
	'geodata-bad-region' => '<nowiki>{{#coordinates:}}</nowiki>: invalid region code format',
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: cannot have more than one primary tag per page',
	'geodata-limit-exceeded' => 'The limit of $1 <nowiki>{{#coordinates:}}</nowiki> tags per page has been exceeded',
	'geodata-broken-tags-category' => 'Pages with malformed coordinate tags',
	'geodata-primary-coordinate' => 'primary',
);

$messages['qqq'] = array(
	'geodata-desc' => '{{desc}}',
	'geodata-limit-exceeded' => '$1 is a number',
	'geodata-broken-tags-category' => 'Name of the tracking category',
	'geodata-primary-coordinate' => 'Localised name of parameter that makes <nowiki>{{#coordinates:}}</nowiki> tag primary',
);

$messages['ru'] = array(
	'geodata-multiple-primary' => '<nowiki>{{#coordinates:}}</nowiki>: нельзя иметь более одной первичной метки на странице',
);