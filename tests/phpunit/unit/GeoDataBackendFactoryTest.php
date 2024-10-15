<?php

namespace GeoData\Test;

use ApiQuery;
use GeoData\Api\QueryGeoSearch;
use GeoData\Api\QueryGeoSearchDb;
use GeoData\Api\QueryGeoSearchElastic;
use MediaWiki\Config\HashConfig;
use MediaWiki\Context\IContextSource;

/**
 * @covers \GeoData\Api\QueryGeoSearch::factory
 */
class GeoDataBackendFactoryTest extends \MediaWikiUnitTestCase {

	protected function mockApiQuery( string $backend = '' ): ApiQuery {
		$context = $this->createNoOpMock( IContextSource::class );

		$apiMain = $this->createMock( \ApiMain::class );
		$apiMain->method( 'getContext' )->willReturn( $context );

		$config = new HashConfig( [ 'GeoDataBackend' => $backend ] );

		$apiQuery = $this->createMock( ApiQuery::class );

		$apiQuery->method( 'getMain' )->willReturn( $apiMain );
		$apiQuery->method( 'getConfig' )->willReturn( $config );

		return $apiQuery;
	}

	/**
	 * @dataProvider provider
	 */
	public function testFactory( string $geoDataBackend, string $expectedClass ) {
		$apiQuery = $this->mockApiQuery( $geoDataBackend );
		$queryGeoSearchBackend = QueryGeoSearch::factory( $apiQuery, 'test' );

		self::assertInstanceOf( $expectedClass, $queryGeoSearchBackend );
	}

	public static function provider(): array {
		return [
			[ 'elastic', QueryGeoSearchElastic::class ],
			[ 'db', QueryGeoSearchDb::class ],
		];
	}

	public function testFactoryThrowErrorWhenBackendIsNotSet() {
		$apiQuery = $this->mockApiQuery();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'GeoDataBackend data backend cannot be empty' );
		QueryGeoSearch::factory( $apiQuery, 'test' );
	}
}
