<?php

namespace GeoData;

use CirrusSearch\CirrusSearch;
use CirrusSearch\CrossSearchStrategy;
use CirrusSearch\HashSearchConfig;
use CirrusSearch\Query\KeywordFeatureAssertions;
use GeoData\Search\CirrusGeoFeature;
use GeoData\Search\CirrusNearCoordBoostFeature;
use GeoData\Search\CirrusNearCoordFilterFeature;
use GeoData\Search\CirrusNearTitleBoostFeature;
use GeoData\Search\CirrusNearTitleFilterFeature;
use GeoData\Search\GeoRadiusFunctionScoreBuilder;
use HashConfig;
use LinkCacheTestTrait;
use MediaWikiTestCase;
use Title;
use Wikimedia\Rdbms\DBConnRef;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LoadBalancer;
use Wikimedia\Rdbms\MaintainableDBConnRef;

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @covers \GeoData\Search\CirrusGeoFeature
 * @covers \GeoData\Search\CirrusNearCoordFilterFeature
 * @covers \GeoData\Search\CirrusNearTitleBoostFeature
 * @covers \GeoData\Search\CirrusNearCoordBoostFeature
 * @covers \GeoData\Search\CirrusNearTitleFilterFeature
 * @group GeoData
 */
class GeoFeatureTest extends MediaWikiTestCase {
	use LinkCacheTestTrait;

	/** @var KeywordFeatureAssertions */
	private $kwAssert;

	public function __construct( $name = null, array $data = [], $dataName = '' ) {
		MediaWikiTestCase::__construct( $name, $data, $dataName );
	}

	protected function setUp(): void {
		parent::setUp();
		if ( !class_exists( CirrusSearch::class ) ) {
			$this->markTestSkipped( 'CirrusSearch not installed, skipping' );
		}
		$this->kwAssert = new KeywordFeatureAssertions( $this );
	}

	public function parseDistanceProvider() {
		return [
			'unknown units returns null' => [
				null,
				'100fur',
			],
			'gibberish returns null' => [
				null,
				'gibberish',
			],
			'no space allowed between numbers and units' => [
				null,
				'100 m',
			],
			'meters' => [
				100,
				'100m',
			],
			'kilometers' => [
				1000,
				'1km',
			],
			'yards' => [
				366,
				'400yd',
			],
			'one mile rounds down' => [
				1609,
				'1mi',
			],
			'two miles rounds up' => [
				'3219',
				'2mi',
			],
			'1000 feet rounds up' => [
				305,
				'1000ft',
			],
			'3000 feet rounds down' => [
				914,
				'3000ft',
			],
			'small requests are bounded' => [
				10,
				'1ft',
			],
			'allows large inputs' => [
				4321000,
				'4321km',
			],
		];
	}

	/**
	 * @dataProvider parseDistanceProvider
	 */
	public function testParseDistance( $expected, $distance ) {
		$this->assertEquals( $expected, CirrusGeoFeature::parseDistance( $distance ) );
	}

	public function parseGeoNearbyProvider() {
		return [
			'random input' => [
				[ null, 0 ],
				'gibberish'
			],
			'random input with comma' => [
				[ null, 0 ],
				'gibberish,42.42'
			],
			'random input with valid radius prefix' => [
				[ null, 0 ],
				'20km,42.42,invalid',
			],
			'valid coordinate, default radius' => [
				[
					[ 'lat' => 1.2345, 'lon' => 2.3456, 'globe' => 'earth' ],
					5000,
				],
				'1.2345,2.3456',
			],
			'valid coordinate, specific radius in meters' => [
				[
					[ 'lat' => -5.4321, 'lon' => 42.345, 'globe' => 'earth' ],
					4321,
				],
				'4321m,-5.4321,42.345',
			],
			'valid coordinate, specific radius in kilmeters' => [
				[
					[ 'lat' => 0, 'lon' => 42.345, 'globe' => 'earth' ],
					7000,
				],
				'7km,0,42.345',
			],
			'out of bounds positive latitude' => [
				[ null, 0 ],
				'90.1,0'
			],
			'out of bounds negative latitude' => [
				[ null, 0 ],
				'-90.1,17',
			],
			'out of bounds positive longitude' => [
				[ null, 0 ],
				'49,180.1',
			],
			'out of bounds negative longitude' => [
				[ null, 0 ],
				'49,-180.001',
			],
			'valid coordinate with spaces' => [
				[
					[ 'lat' => 1.2345, 'lon' => 9.8765, 'globe' => 'earth' ],
					5000
				],
				'1.2345, 9.8765'
			],
		];
	}

	/**
	 * @dataProvider parseGeoNearbyProvider
	 */
	public function testParseGeoNearby( $expected, $value ) {
		$config = new HashConfig( [ 'DefaultGlobe' => 'earth' ] );
		$features = [
			new CirrusNearCoordFilterFeature( $config ),
			new CirrusNearCoordBoostFeature( $config )
		];
		foreach ( $features as $feature ) {
			$query = $feature->getKeywordPrefixes()[0] . ':"' . $value . '"';
			$this->kwAssert->assertParsedValue( $feature, $query, $expected );
		}

		$searchConfig = new HashSearchConfig( [
			'DefaultGlobe' => 'earth',
			'GeoDataRadiusScoreOverrides' => [],

		] );
		$boostFunction = null;
		if ( $expected[0] !== null ) {
			$boostFunction = new GeoRadiusFunctionScoreBuilder( $searchConfig, 1,
				new Coord( $expected[0]['lat'], $expected[0]['lon'], $expected[0]['globe'] ), $expected[1] );
		}
		$boostFeature = new CirrusNearCoordBoostFeature( $config );
		$query = $boostFeature->getKeywordPrefixes()[0] . ':"' . $value . '"';
		$this->kwAssert->assertBoost( $boostFeature, $query, $boostFunction, null, $searchConfig );

		$filterQuery = null;
		if ( $expected[0] !== null ) {
			$filterQuery = CirrusNearTitleFilterFeature::createQuery(
				new Coord( $expected[0]['lat'], $expected[0]['lon'], $expected[0]['globe'] ),
				$expected[1]
			);
		}
		$filterFeature = new CirrusNearCoordFilterFeature( $config );
		$query = $filterFeature->getKeywordPrefixes()[0] . ':"' . $value . '"';
		$this->kwAssert->assertFilter( $filterFeature, $query, $filterQuery, null, $searchConfig );
	}

	public function parseGeoNearbyTitleProvider() {
		return [
			'basic page lookup' => [
				[
					[ 'lat' => 1.2345, 'lon' => 5.4321 ],
					5000,
					7654321,
				],
				'San Francisco'
			],
			'basic page lookup with radius in meters' => [
				[
					[ 'lat' => 1.2345, 'lon' => 5.4321 ],
					1234,
					7654321,
				],
				'1234m,San Francisco'
			],
			'basic page lookup with radius in kilometers' => [
				[
					[ 'lat' => 1.2345, 'lon' => 5.4321 ],
					2000,
					7654321,
				],
				'2km,San Francisco'
			],
			'basic page lookup with space between radius and name' => [
				[
					[ 'lat' => 1.2345, 'lon' => 5.4321 ],
					2000,
					7654321,
				],
				'2km, San Francisco'
			],
			'page with comma in name' => [
				[
					[ 'lat' => 1.2345, 'lon' => 5.4321 ],
					5000,
					1234567,
				],
				'Washington, D.C.'
			],
			'page with comma in name and radius in kilometers' => [
				[
					[ 'lat' => 1.2345, 'lon' => 5.4321 ],
					7000,
					1234567,
				],
				'7km,Washington, D.C.'
			],
			'unknown page lookup' => [
				[ null, 0, '' ],
				'Unknown Title',
			],
			'unknown page lookup with radius' => [
				[ null, 0, '' ],
				'4km, Unknown Title',
			],
		];
	}

	/**
	 * @dataProvider parseGeoNearbyTitleProvider
	 */
	public function testParseGeoNearbyTitle( $expected, $value ) {
		// Replace database with one that will return our fake coordinates if asked
		$dbMocker = function ( $db ) {
			$db->method( 'select' )
				->with( 'geo_tags', $this->anything(), $this->anything(), $this->anything() )
				->willReturn( [
					(object)[ 'gt_lat' => 1.2345, 'gt_lon' => 5.4321 ],
				] );
			// Tell LinkCache all titles not explicitly added don't exist
			$db->method( 'selectRow' )
				->with(
					$this->logicalOr( 'page', [ 'page' ] ),
					$this->anything(),
					$this->anything(),
					$this->anything()
				)
				->willReturn( false );
			return $db;
		};
		// Inject mock database into a mock LoadBalancer
		$lb = $this->createMock( LoadBalancer::class );
		$lb->method( 'getConnection' )
			->willReturn( $dbMocker( $this->createMock( IDatabase::class ) ) );
		$lb->method( 'getConnectionRef' )
			->willReturn( $dbMocker( $this->createMock( DBConnRef::class ) ) );
		$lb->method( 'getMaintenanceConnectionRef' )
			->willReturn( $dbMocker( $this->createMock( MaintainableDBConnRef::class ) ) );
		$this->setService( 'DBLoadBalancer', $lb );

		// Inject fake San Francisco page into LinkCache so it "exists"
		$this->addGoodLinkObject( 7654321, Title::newFromText( 'San Francisco' ) );
		// Inject fake page with comma in it as well
		$this->addGoodLinkObject( 1234567, Title::newFromText( 'Washington, D.C.' ) );

		$config = new HashConfig( [ 'DefaultGlobe' => 'earth' ] );

		/**
		 * @var $features \CirrusSearch\Query\SimpleKeywordFeature[]
		 */
		$features = [];
		$features[] = new CirrusNearTitleBoostFeature( $config );
		$features[] = new CirrusNearTitleFilterFeature( $config );
		if ( $expected[0] !== null ) {
			$expected[0] = new Coord( $expected[0]['lat'], $expected[0]['lon'], 'earth' );
		}
		foreach ( $features as $feature ) {
			$query = $feature->getKeywordPrefixes()[0] . ':"' . $value . '"';
			$this->kwAssert->assertParsedValue( $feature, $query, null, [] );
			$this->kwAssert->assertExpandedData( $feature, $query, $expected );
			$this->kwAssert->assertCrossSearchStrategy( $feature, $query,
				CrossSearchStrategy::hostWikiOnlyStrategy() );
		}
		$searchConfig = new HashSearchConfig( [
			'GeoDataRadiusScoreOverrides' => [],
			'DefaultGlobe' => 'earth',
		] );

		$boostFeature = new CirrusNearTitleBoostFeature( $searchConfig );
		$boostFunction = null;
		if ( $expected[0] !== null ) {
			$boostFunction = new GeoRadiusFunctionScoreBuilder( $searchConfig, 1,
				$expected[0], $expected[1] );
		}
		$query = $boostFeature->getKeywordPrefixes()[0] . ':"' . $value . '"';
		$this->kwAssert->assertBoost( $boostFeature, $query, $boostFunction, null, $searchConfig );

		$filterQuery = null;
		if ( $expected[0] !== null ) {
			$filterQuery = CirrusNearTitleFilterFeature::createQuery( $expected[0],
				$expected[1], $expected[2] );
		}
		$filterFeature = new CirrusNearTitleFilterFeature( $searchConfig );
		$query = $filterFeature->getKeywordPrefixes()[0] . ':"' . $value . '"';
		$this->kwAssert->assertFilter( $filterFeature, $query, $filterQuery, null, $searchConfig );
	}

	public function geoWarningsProvider() {
		return [
			'coordinates must be two or three pieces' => [
				[ [ 'geodata-search-feature-invalid-coordinates', 'nearcoord', 'hi' ] ],
				[ 'nearcoord', 'hi' ],
			],
			'coordinates must be two or three pieces (boost version)' => [
				[ [ 'geodata-search-feature-invalid-coordinates', 'boost-nearcoord', 'hi' ] ],
				[ 'boost-nearcoord', 'hi' ],
			],
			'three piece coordinates must use valid radius with qualifier' => [
				[ [ 'geodata-search-feature-invalid-distance', 'nearcoord', '40s' ] ],
				[ 'nearcoord', '40s,12,21' ]
			],
			'three piece coordinates must use valid radius with qualifier (boost version)' => [
				[ [ 'geodata-search-feature-invalid-distance', 'boost-nearcoord', '40s' ] ],
				[ 'boost-nearcoord', '40s,12,21' ]
			],
			'coordinates must be valid earth coordinates' => [
				[ [ 'geodata-search-feature-invalid-coordinates', 'boost-nearcoord', '12345,123' ] ],
				[ 'boost-nearcoord', '12345,123' ],
			],
			'titles must be known' => [
				[ [ 'geodata-search-feature-unknown-title', 'neartitle', 'Some unknown page' ] ],
				[ 'neartitle', '10km,Some unknown page' ],
			],
			'titles must be known (boost verrsion)' => [
				[ [ 'geodata-search-feature-unknown-title', 'boost-neartitle', 'Some unknown page' ] ],
				[ 'boost-neartitle', '10km,Some unknown page' ],
			],
			'titles must have coordinates' => [
				[ [ 'geodata-search-feature-title-no-coordinates', 'GeoFeatureTest-GeoWarnings-Page' ] ],
				[ 'neartitle', 'GeoFeatureTest-GeoWarnings-Page' ],
			],
			'titles must have coordinates (boost version)' => [
				[ [ 'geodata-search-feature-title-no-coordinates', 'GeoFeatureTest-GeoWarnings-Page' ] ],
				[ 'boost-neartitle', 'GeoFeatureTest-GeoWarnings-Page' ],
			],
		];
	}

	/**
	 * @dataProvider geoWarningsProvider
	 */
	public function testGeoWarnings( $expected, array $keyAndValue ) {
		$features = [];
		$config = new HashConfig( [ 'DefaultGlobe' => 'earth' ] );
		$feature = new CirrusNearCoordBoostFeature( $config );
		$features[$feature->getKeywordPrefixes()[0]] = $feature;
		$feature = new CirrusNearCoordFilterFeature( $config );
		$features[$feature->getKeywordPrefixes()[0]] = $feature;
		$feature = new CirrusNearTitleFilterFeature( $config );
		$features[$feature->getKeywordPrefixes()[0]] = $feature;
		$feature = new CirrusNearTitleBoostFeature( $config );
		$features[$feature->getKeywordPrefixes()[0]] = $feature;

		$feature = $features[$keyAndValue[0]];
		$query = $keyAndValue[0] . ':"' . $keyAndValue[1] . '"';

		// Inject fake page into LinkCache so it "exists"
		$this->addGoodLinkObject( 98765, Title::newFromText( 'GeoFeatureTest-GeoWarnings-Page' ) );

		$this->kwAssert->assertWarnings( $feature, $expected, $query );
	}
}
