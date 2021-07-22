<?php

namespace GeoData\Test;

use ApiMain;
use ApiUsageException;
use FauxRequest;
use GeoData\Api\QueryGeoSearch;
use MediaWikiTestCase;

/**
 * @covers \GeoData\Api\QueryGeoSearch
 *
 * @group GeoData
 */
class GeoSearchTest extends MediaWikiTestCase {
	public function setUp(): void {
		$this->setMwGlobals( 'wgAPIListModules',
			[
				'geosearch' => [
					'class' => QueryGeoSearch::class
				]
			]
		);
		parent::setUp();
	}

	private function request( array $params ) {
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

	public function provideRequiredParams() {
		return [
			[
				[],
				'coord, page or bbox are required'
			],
			[
				[ 'gscoord' => '1|2', 'gspage' => 'foo' ],
				'Must have only one of coord, page or bbox'
			],
			// @fixme: [
			// [ 'gsbbox' => '10|170|-10|-170' ],
			// 'Fail if bounding box is too big'
			// ],
			[
				[ 'gsbbox' => '10|170|10|170' ],
				'Fail if bounding box is too small'
			],
		];
	}
}
