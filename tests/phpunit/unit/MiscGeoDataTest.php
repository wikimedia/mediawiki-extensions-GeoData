<?php

namespace GeoData\Test;

use GeoData\Api\QueryGeoSearchDb;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiQuery;
use MediaWiki\Config\HashConfig;
use MediaWiki\Context\IContextSource;
use MediaWikiUnitTestCase;

/**
 * @group GeoData
 */
class MiscGeoDataTest extends MediaWikiUnitTestCase {
	/**
	 * @covers \GeoData\Api\QueryGeoSearchDb::intRange
	 * @dataProvider provideIntRangeData
	 */
	public function testIntRange( float $min, float $max, array $expected ) {
		$context = $this->createNoOpMock( IContextSource::class );

		$apiMain = $this->createMock( ApiMain::class );
		$apiMain->method( 'getContext' )->willReturn( $context );

		$apiBase = $this->createMock( ApiQuery::class );
		$apiBase->method( 'getMain' )->willReturn( $apiMain );

		$config = new HashConfig( [
			'GeoDataIndexGranularity' => 10,
		] );

		$queryGeoSearchDb = new QueryGeoSearchDb(
			$apiBase,
			'test',
			$config
		);
		$this->assertEquals( $expected, $queryGeoSearchDb->intRange( $min, $max ) );
	}

	public static function provideIntRangeData() {
		return [
			[ 37.697, 37.877, [ 377, 378, 379 ] ],
			[ 9.99, 10.01, [ 100 ] ],
			[ 179.9, -179.9, [ -1800, -1799, 1799, 1800 ] ]
		];
	}
}
