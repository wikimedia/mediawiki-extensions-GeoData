<?php

namespace GeoData\Test;

use ApiQuery;
use GeoData\Api\QueryGeoSearchDb;
use GeoData\Api\QueryGeoSearchElastic;
use GeoData\Hooks;
use MediaWiki\Config\HashConfig;

/**
 * @covers \GeoData\Hooks::createQueryGeoSearchBackend
 */
class GeoDataBackendFactoryTest extends \MediaWikiUnitTestCase {

	protected function mockApiQuery( string $backend = '' ): ApiQuery {
		$context = $this->createNoOpMock( \IContextSource::class );

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
	public function testCreateQueryGeoSearchBackend( string $geoDataBackend, string $expectedClass ) {
		$apiQuery = $this->mockApiQuery( $geoDataBackend );
		$queryGeoSearchBackend = Hooks::createQueryGeoSearchBackend( $apiQuery, 'test' );

		self::assertInstanceOf( $expectedClass, $queryGeoSearchBackend );
	}

	public static function provider(): array {
		return [
			[ 'elastic', QueryGeoSearchElastic::class ],
			[ 'db', QueryGeoSearchDb::class ],
		];
	}

	public function testCreateQueryGeoSearchBackendThrowErrorWhenBackendIsNotSet() {
		$apiQuery = $this->mockApiQuery();

		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'GeoDataBackend data backend cannot be empty' );
		Hooks::createQueryGeoSearchBackend( $apiQuery, 'test' );
	}
}
