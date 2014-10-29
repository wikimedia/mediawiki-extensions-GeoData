<?php
/**
 * This query adds an <coordinates> subelement to all pages with the list of coordinated present on those pages.
 */
class ApiQueryCoordinates extends ApiQueryBase {

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'co' );
	}

	public function execute() {
		$titles = $this->getPageSet()->getGoodTitles();
		if ( count( $titles ) == 0 ) {
			return;
		}

		$params = $this->extractRequestParams();
		$from = $this->getFromCoord( $params );
		$this->addTables( 'geo_tags' );
		$this->addFields( array( 'gt_id', 'gt_page_id', 'gt_lat', 'gt_lon', 'gt_primary', 'gt_globe' ) );
		$mapping = Coord::getFieldMapping();
		foreach( $params['prop'] as $prop ) {
			if ( isset( $mapping[$prop] ) ) {
				$this->addFields( $mapping[$prop] );
			}
		}
		$this->addWhereFld( 'gt_page_id', array_keys( $titles ) );
		$primary = $params['primary'];
		$this->addWhereIf( array( 'gt_primary' => intval( $primary === 'primary' ) ), $primary !== 'all' );

		if ( isset( $params['continue'] ) ) {
			$parts = explode( '|', $params['continue'] );
			if ( count( $parts ) != 2 || !is_numeric( $parts[0] ) || !is_numeric( $parts[0] ) ) {
				$this->dieUsage( "Invalid continue parameter. You should pass the " .
					"original value returned by the previous query", "_badcontinue" );
			}
			$parts[0] = intval( $parts[0] );
			$parts[1] = intval( $parts[1] );
			$this->addWhere(
				"gt_page_id > {$parts[0]} OR ( gt_page_id = {$parts[0]} AND gt_id >= {$parts[1]} )"
			);
		} else {
			$this->addOption( 'USE INDEX', 'gt_page_id' );
		}
		
		$this->addOption( 'ORDER BY', array( 'gt_page_id', 'gt_id' ) );
		$this->addOption( 'LIMIT', $params['limit'] + 1 );

		$res = $this->select( __METHOD__ );

		$count = 0;
		foreach ( $res as $row ) {
			if ( ++$count > $params['limit'] ) {
				$this->setContinueEnumParameter( 'continue', $row->gt_page_id . '|' . $row->gt_id );
				break;
			}
			$vals = array(
				'lat' => floatval( $row->gt_lat ),
				'lon' => floatval( $row->gt_lon ),
			);
			if ( $row->gt_primary )	{
				$vals['primary'] = '';
			}
			foreach( $params['prop'] as $prop ) {
				if ( isset( $mapping[$prop] ) && isset( $row->{$mapping[$prop]} ) ) {
					$field = $mapping[$prop];
					$vals[$prop] = $row->$field;
				}
			}
			if ( $from && $row->gt_globe == $from->globe ) {
				$vals['dist'] = round(
					GeoDataMath::distance( $from->lat, $from->lon, $row->gt_lat, $row->gt_lon ),
					1
				);
			}
			$fit = $this->addPageSubItem( $row->gt_page_id, $vals );
			if ( !$fit ) {
				$this->setContinueEnumParameter( 'continue', $row->gt_page_id . '|' . $row->gt_id );
			}
		}
	}

	/**
	 * @param array $params
	 * @return Coord|null
	 * @throws MWException
	 */
	private function getFromCoord( array $params ) {
		$this->requireMaxOneParameter( $params, 'distancefrompoint', 'distancefrompage' );
		if ( $params['distancefrompoint'] !== null ) {
			$arr = explode( '|', $params['distancefrompoint'] );
			if ( count( $arr ) != 2 || !GeoData::validateCoord( $arr[0], $arr[1], 'earth' ) ) {
				$this->dieUsage( 'Invalid coordinate provided', '_invalid-coord' );
			}
			return new Coord( $arr[0], $arr[1], 'earth' );
		}
		if ( $params['distancefrompage'] !== null ) {
			$title = Title::newFromText( $params['distancefrompage'] );
			if ( !$title ) {
				$this->dieUsage( "Page ``{$params['distancefrompage']}'' does not exist", '_invalid-page' );
			}
			$coord = GeoData::getPageCoordinates( $title );
			if ( !$coord ) {
				$this->dieUsage( "Page ``{$params['distancefrompage']}'' has no primary coordinates", '_no-coordinates' );
			}
			if ( $coord->globe != 'earth' ) {
				$this->dieUsage( "This page's coordinates are not on Earth", '_notonearth' );
			}
			return $coord;
		}
		return null;
	}

	public function getCacheMode( $params ) {
		return 'public';
	}

	public function getAllowedParams() {
		return array(
			'limit' => array(
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			),
			'continue' => array(
				ApiBase::PARAM_TYPE => 'string',
				/** @todo Once support for MediaWiki < 1.25 is dropped, just use ApiBase::PARAM_HELP_MSG directly */
				constant( 'ApiBase::PARAM_HELP_MSG' ) ?: '' => 'api-help-param-continue',
			),
			'prop' => array(
				ApiBase::PARAM_TYPE => array( 'type', 'name', 'dim', 'country', 'region', 'globe' ),
				ApiBase::PARAM_DFLT => 'globe',
				ApiBase::PARAM_ISMULTI => true,
			),
			'primary' => array(
				ApiBase::PARAM_TYPE => array( 'primary', 'secondary', 'all' ),
				ApiBase::PARAM_DFLT => 'primary',
			),
			'distancefrompoint' => array(
				ApiBase::PARAM_TYPE => 'string',
				/** @todo Once support for MediaWiki < 1.25 is dropped, just use ApiBase::PARAM_HELP_MSG_APPEND directly */
				constant( 'ApiBase::PARAM_HELP_MSG_APPEND' ) ?: '' => array(
					'geodata-api-help-coordinates-format',
				),
			),
			'distancefrompage' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
		);
	}

	/**
	 * @deprecated since MediaWiki core 1.25
	 */
	public function getParamDescription() {
		return array(
			'limit' => 'How many coordinates to return',
			'continue' => 'When more results are available, use this to continue',
			'prop' => 'What additional coordinate properties to return',
			'primary' => "Whether to return only primary coordinates (``primary''), secondary (``secondary'') or both (``all'')",
			'distancefrompoint' => 'Return distance in meters of every result on Earth from these coordinates: latitude and longitude separated by pipe (|)',
			'distancefrompage' => 'Return distance in meters of every result on Earth from the coordinates of this page',
		);
	}

	/**
	 * @deprecated since MediaWiki core 1.25
	 */
	public function getDescription() {
		return 'Returns coordinates of the given page(s)';
	}

	/**
	 * @deprecated since MediaWiki core 1.25
	 */
	public function getExamples() {
		return array(
			'Get a list of coordinates of the [[Main Page]]:',
			'  api.php?action=query&prop=coordinates&titles=Main%20Page',
		);
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 */
	protected function getExamplesMessages() {
		return array(
			'action=query&prop=coordinates&titles=Main%20Page'
				=> 'apihelp-query+coordinates-example-1',
		);
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:GeoData#prop.3Dcoordinates';
	}
}
