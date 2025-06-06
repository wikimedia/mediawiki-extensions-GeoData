<?php

namespace GeoData\Test;

use GeoData\Api\QueryGeoSearch;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiUsageException;
use MediaWiki\MainConfigNames;
use MediaWiki\Request\FauxRequest;
use MediaWikiIntegrationTestCase;

/**
 * @covers \GeoData\Api\QueryGeoSearch
 *
 * @group GeoData
 */
class GeoSearchTest extends MediaWikiIntegrationTestCase {
	public function setUp(): void {
		$this->overrideConfigValue( MainConfigNames::APIListModules,
			[
				'geosearch' => [
					'class' => QueryGeoSearch::class
				]
			]
		);
		parent::setUp();
	}

	private function request( array $params ): void {
		$params += [ 'action' => 'query', 'list' => 'geosearch' ];
		$request = new FauxRequest( $params );

		$api = new ApiMain( $request );
		$api->execute();
	}

	/**
	 * @dataProvider provideRequiredParams
	 */
	public function testRequiredParams( array $params ) {
		$this->expectException( ApiUsageException::class );
		$this->request( $params );
	}

	public static function provideRequiredParams() {
		return [
			'coord, page or bbox are required' => [
				[],
			],
			'Must have only one of coord, page or bbox' => [
				[ 'gscoord' => '1|2', 'gspage' => 'foo' ],
			],
			// @fixme: 'Fail if bounding box is too big' => [
			// [ 'gsbbox' => '10|170|-10|-170' ],
			// ],
			'Fail if bounding box is too small' => [
				[ 'gsbbox' => '10|170|10|170' ],
			],
		];
	}
}
