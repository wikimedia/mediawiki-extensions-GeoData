<?php

namespace GeoData\Test;

use ApiMain;
use FauxRequest;
use GeoData\ApiQueryGeoSearch;
use MediaWikiTestCase;
use UsageException;

/**
 * @group GeoData
 */
class GeoSearchTest extends MediaWikiTestCase {
	public function setUp() {
		$this->setMwGlobals( 'wgAPIListModules', [ 'geosearch' => 'GeoData\Test\MockApiQueryGeoSearch' ] );
		parent::setUp();
	}

	private function request( array $params ) {
		$params += [ 'action' => 'query', 'list' => 'geosearch' ];
		$request = new FauxRequest( $params );

		$api = new ApiMain( $request );
		$api->execute();
	}

	/**
	 * @expectedException UsageException
	 * @dataProvider provideRequiredParams
	 */
	public function testRequiredParams( array $params ) {
		$this->request( $params );
	}

	public function provideRequiredParams() {
		return [
			[ [], 'coord, page or bbox are required' ],
			[ [ 'gscoord' => '1|2', 'gspage' => 'foo' ], 'Must have only one of coord, page or bbox' ],
			// @fixme: [ [ 'gsbbox' => '10|170|-10|-170' ], 'Fail if bounding box is too big' ],
			[ [ 'gsbbox' => '10|170|10|170' ], 'Fail if bounding box is too small' ],
		];
	}
}

class MockApiQueryGeoSearch extends ApiQueryGeoSearch {
}
