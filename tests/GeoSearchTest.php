<?php

/**
 * @group GeoData
 */
class GeoSearchTest extends MediaWikiTestCase {
	public function setUp() {
		$this->setMwGlobals( 'wgAPIListModules', array( 'geosearch' => 'MockApiQueryGeoSearch' ) );
		parent::setUp();
	}

	private function request( array $params ) {
		$params += array( 'action' => 'query', 'list' => 'geosearch' );
		$request = new FauxRequest( $params );

		$api = new ApiMain( $request );
		return $api->execute();
	}

	/**
	 * @expectedException UsageException
	 * @dataProvider provideRequiredParams
	 */
	public function testRequiredParams( array $params ) {
		$this->request( $params );
	}

	public function provideRequiredParams() {
		return array(
			array( array(), 'coord, page or bbox are required' ),
			array( array( 'gscoord' => '1|2', 'gspage' => 'foo' ), 'Must have only one of coord, page or bbox' ),
			array( array( 'gsbbox' => '10|170|-10|-170' ), 'Fail if bounding box is too big' ),
			array( array( 'gsbbox' => '10|170|10|170' ), 'Fail if bounding box is too small' ),
		);
	}
}

class MockApiQueryGeoSearch extends ApiQueryGeoSearch {
}
