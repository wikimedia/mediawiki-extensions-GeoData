<?php

if ( PHP_SAPI !== 'cli' ) {
	die( 'This is a command-line script' );
}

$site = 'http://localhost/w/api.php';
$times = [];

echo "Load-testing $site with GeoData requests, press Ctrl+Break to stop...\n";

do {
	$lat = mt_rand( -900000, 900000 ) / 10000;
	$lon = mt_rand( -1800000, 1800000 ) / 10000;
	$url = "{$site}?action=query&list=geosearch&format=json&gsradius=100&gscoord=$lat|$lon";
	echo "[$lat, $lon]";
	$time = microtime( true );
	$response = file_get_contents( $url );
	$time = microtime( true ) - $time;
	array_push( $times, $time );
	if ( count( $times ) > 20 ) {
		array_shift( $times );
	}
	$avg = round( array_sum( $times ) / count( $times ), 3 );
	$time = round( $time, 3 );
	echo ": $time s, average: $avg s\n";
} while ( true );
