<?php

namespace GeoData;

use CirrusSearch\CrossSearchStrategy;
use CirrusSearch\HashSearchConfig;
use CirrusSearch\Query\KeywordFeatureAssertions;
use GeoData\Search\CirrusNearCoordBoostFeature;
use GeoData\Search\CirrusNearCoordFilterFeature;
use GeoData\Search\CirrusNearTitleBoostFeature;
use GeoData\Search\CirrusNearTitleFilterFeature;
use GeoData\Search\GeoRadiusFunctionScoreBuilder;
use LinkCacheTestTrait;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\IReadableDatabase;
use Wikimedia\Rdbms\LoadBalancer;
use Wikimedia\Rdbms\SelectQueryBuilder;

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
class GeoFeatureTest extends MediaWikiIntegrationTestCase {
	use LinkCacheTestTrait;

	/** @var KeywordFeatureAssertions */
	private $kwAssert;

	protected function setUp(): void {
		parent::setUp();
		$this->markTestSkippedIfExtensionNotLoaded( 'CirrusSearch' );
		$this->kwAssert = new KeywordFeatureAssertions( $this );
	}

	public static function parseDistanceProvider() {
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
				3219,
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
	public function testParseDistance( ?int $expected, string $distance ) {
		// Call the method via a random class that uses the trait since you
		// can't call trait methods directly in PHP 8.1+
		$this->assertSame( $expected, CirrusNearCoordFilterFeature::parseDistance( $distance ) );
	}

	public static function parseGeoNearbyProvider() {
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
					[ 'lat' => 1.2345, 'lon' => 2.3456, 'globe' => Globe::EARTH ],
					5000,
				],
				'1.2345,2.3456',
			],
			'valid coordinate, specific radius in meters' => [
				[
					[ 'lat' => -5.4321, 'lon' => 42.345, 'globe' => Globe::EARTH ],
					4321,
				],
				'4321m,-5.4321,42.345',
			],
			'valid coordinate, specific radius in kilmeters' => [
				[
					[ 'lat' => 0, 'lon' => 42.345, 'globe' => Globe::EARTH ],
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
					[ 'lat' => 1.2345, 'lon' => 9.8765, 'globe' => Globe::EARTH ],
					5000
				],
				'1.2345, 9.8765'
			],
		];
	}

	/**
	 * @dataProvider parseGeoNearbyProvider
	 */
	public function testParseGeoNearby( array $expected, string $value ) {
		$features = [
			new CirrusNearCoordFilterFeature(),
			new CirrusNearCoordBoostFeature()
		];
		foreach ( $features as $feature ) {
			$query = $feature->getKeywordPrefixes()[0] . ':"' . $value . '"';
			$this->kwAssert->assertParsedValue( $feature, $query, $expected );
		}

		$searchConfig = new HashSearchConfig( [] );
		$boostFunction = null;
		if ( $expected[0] !== null ) {
			$boostFunction = new GeoRadiusFunctionScoreBuilder( $searchConfig, 1,
				new Coord( $expected[0]['lat'], $expected[0]['lon'], $expected[0]['globe'] ), $expected[1] );
		}
		$boostFeature = new CirrusNearCoordBoostFeature();
		$query = $boostFeature->getKeywordPrefixes()[0] . ':"' . $value . '"';
		$this->kwAssert->assertBoost( $boostFeature, $query, $boostFunction, null, $searchConfig );

		$filterQuery = null;
		if ( $expected[0] !== null ) {
			$filterQuery = CirrusNearTitleFilterFeature::createQuery(
				new Coord( $expected[0]['lat'], $expected[0]['lon'], $expected[0]['globe'] ),
				$expected[1]
			);
		}
		$filterFeature = new CirrusNearCoordFilterFeature();
		$query = $filterFeature->getKeywordPrefixes()[0] . ':"' . $value . '"';
		$this->kwAssert->assertFilter( $filterFeature, $query, $filterQuery, null, $searchConfig );
	}

	public static function parseGeoNearbyTitleProvider() {
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
	public function testParseGeoNearbyTitle( array $expected, string $value ) {
		// Replace database with one that will return our fake coordinates if asked
		$dbMocker = function ( MockObject $db ) {
			$queryBuilder = $this->createMock( SelectQueryBuilder::class );
			$queryBuilder->method( $this->logicalOr( 'select', 'from', 'where', 'caller' ) )->willReturnSelf();
			$queryBuilder->method( 'fetchResultSet' )
				->willReturn( new FakeResultWrapper( [
					(object)[ 'gt_lat' => 1.2345, 'gt_lon' => 5.4321, 'gt_globe' => Globe::EARTH ],
				] ) );
			$db->method( 'newSelectQueryBuilder' )
				->willReturn( $queryBuilder );
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
		$this->setService( 'DBLoadBalancer', $lb );

		// Inject fake San Francisco page into LinkCache so it "exists"
		$this->addGoodLinkObject( 7654321, Title::newFromText( 'San Francisco' ) );
		// Inject fake page with comma in it as well
		$this->addGoodLinkObject( 1234567, Title::newFromText( 'Washington, D.C.' ) );

		/**
		 * @var $features \CirrusSearch\Query\SimpleKeywordFeature[]
		 */
		$features = [];
		$features[] = new CirrusNearTitleBoostFeature();
		$features[] = new CirrusNearTitleFilterFeature();
		if ( $expected[0] !== null ) {
			$expected[0] = new Coord( $expected[0]['lat'], $expected[0]['lon'] );
		}
		foreach ( $features as $feature ) {
			$query = $feature->getKeywordPrefixes()[0] . ':"' . $value . '"';
			$this->kwAssert->assertParsedValue( $feature, $query, null, [] );
			$this->kwAssert->assertExpandedData( $feature, $query, $expected );
			$this->kwAssert->assertCrossSearchStrategy( $feature, $query,
				CrossSearchStrategy::hostWikiOnlyStrategy() );
		}

		$searchConfig = new HashSearchConfig( [] );
		$boostFeature = new CirrusNearTitleBoostFeature();
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
		$filterFeature = new CirrusNearTitleFilterFeature();
		$query = $filterFeature->getKeywordPrefixes()[0] . ':"' . $value . '"';
		$this->kwAssert->assertFilter( $filterFeature, $query, $filterQuery, null, $searchConfig );
	}

	public static function geoWarningsProvider() {
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
	public function testGeoWarnings( array $expected, array $keyAndValue ) {
		$features = [];
		$feature = new CirrusNearCoordBoostFeature();
		$features[$feature->getKeywordPrefixes()[0]] = $feature;
		$feature = new CirrusNearCoordFilterFeature();
		$features[$feature->getKeywordPrefixes()[0]] = $feature;
		$feature = new CirrusNearTitleFilterFeature();
		$features[$feature->getKeywordPrefixes()[0]] = $feature;
		$feature = new CirrusNearTitleBoostFeature();
		$features[$feature->getKeywordPrefixes()[0]] = $feature;

		$feature = $features[$keyAndValue[0]];
		$query = $keyAndValue[0] . ':"' . $keyAndValue[1] . '"';

		// Inject fake page into LinkCache and force its page ID so it "exists"
		$titleText = 'GeoFeatureTest-GeoWarnings-Page';
		$title = Title::makeTitle( NS_MAIN, $titleText );
		$title->resetArticleID( 98765 );
		$this->addGoodLinkObject( 98765, $title );
		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->onlyMethods( [ 'newFromText' ] )
			->getMock();
		$titleFactory->method( 'newFromText' )->willReturnCallback(
			static function ( $text, $ns ) use ( $title, $titleText ) {
				if ( $text === $titleText ) {
					return $title;
				}
				$ret = Title::newFromText( $text, $ns );
				if ( $ret ) {
					$ret->resetArticleID( 0 );
				}
				return $ret;
			} );
		$this->setService( 'TitleFactory', $titleFactory );
		$queryBuilder = $this->createMock( SelectQueryBuilder::class );
		$queryBuilder->method( $this->logicalOr( 'select', 'from', 'where', 'caller' ) )->willReturnSelf();
		$queryBuilder->method( 'fetchResultSet' )->willReturn( new FakeResultWrapper( [] ) );
		$db = $this->createMock( IReadableDatabase::class );
		$db->method( 'newSelectQueryBuilder' )->willReturn( $queryBuilder );
		$lb = $this->createMock( ILoadBalancer::class );
		$lb->method( 'getConnection' )->with( DB_REPLICA )->willReturn( $db );
		$this->setService( 'DBLoadBalancer', $lb );

		$this->kwAssert->assertWarnings( $feature, $expected, $query );
	}
}
