<?php

namespace GeoData\Api;

use ApiBase;
use ApiQuery;
use ApiQueryBase;
use GeoData\Coord;
use GeoData\GeoData;
use GeoData\Globe;
use GeoData\Math;
use MWException;
use Title;

/**
 * This query adds an <coordinates> subelement to all pages with the list of coordinated
 * present on those pages.
 */
class QueryCoordinates extends ApiQueryBase {

	/**
	 * @param ApiQuery $query
	 * @param string $moduleName
	 */
	public function __construct( ApiQuery $query, $moduleName ) {
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
		$this->addFields( [ 'gt_id', 'gt_page_id', 'gt_lat', 'gt_lon', 'gt_primary', 'gt_globe' ] );
		$mapping = Coord::getFieldMapping();
		foreach ( $params['prop'] as $prop ) {
			if ( isset( $mapping[$prop] ) ) {
				$this->addFields( $mapping[$prop] );
			}
		}
		$this->addWhereFld( 'gt_page_id', array_keys( $titles ) );
		$primary = $params['primary'];
		$this->addWhereIf(
			[ 'gt_primary' => intval( $primary === 'primary' ) ], $primary !== 'all'
		);

		if ( isset( $params['continue'] ) ) {
			$parts = explode( '|', $params['continue'], 3 );
			$this->dieContinueUsageIf( count( $parts ) !== 2 );
			$this->dieContinueUsageIf( !is_numeric( $parts[0] ) );
			$this->dieContinueUsageIf( !is_numeric( $parts[1] ) );
			$parts[0] = intval( $parts[0] );
			$parts[1] = intval( $parts[1] );
			$this->addWhere(
				"gt_page_id > {$parts[0]} OR ( gt_page_id = {$parts[0]} AND gt_id >= {$parts[1]} )"
			);
		} else {
			$this->addOption( 'USE INDEX', 'gt_page_id' );
		}

		$this->addOption( 'ORDER BY', [ 'gt_page_id', 'gt_id' ] );
		$this->addOption( 'LIMIT', $params['limit'] + 1 );

		$res = $this->select( __METHOD__ );

		$count = 0;
		foreach ( $res as $row ) {
			if ( ++$count > $params['limit'] ) {
				$this->setContinueEnumParameter( 'continue', $row->gt_page_id . '|' . $row->gt_id );
				break;
			}
			$vals = [
				'lat' => floatval( $row->gt_lat ),
				'lon' => floatval( $row->gt_lon ),
				'primary' => boolval( $row->gt_primary ),
			];
			foreach ( $params['prop'] as $prop ) {
				if ( isset( $mapping[$prop] ) && isset( $row->{$mapping[$prop]} ) ) {
					$field = $mapping[$prop];
					$vals[$prop] = $row->$field;
				}
			}
			if ( $from && $row->gt_globe == $from->globe ) {
				$vals['dist'] = round(
					Math::distance( $from->lat, $from->lon, $row->gt_lat, $row->gt_lon ),
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
		$globe = new Globe( 'earth' );
		if ( $params['distancefrompoint'] !== null ) {
			$arr = explode( '|', $params['distancefrompoint'] );
			if ( count( $arr ) != 2 || !$globe->coordinatesAreValid( $arr[0], $arr[1] ) ) {
				$this->dieWithError( 'apierror-geodata-badcoord', 'invalid-coord' );
			}
			return new Coord( (float)$arr[0], (float)$arr[1], 'earth' );
		}
		if ( $params['distancefrompage'] !== null ) {
			$title = Title::newFromText( $params['distancefrompage'] );
			if ( !$title ) {
				$this->dieWithError( [
					'apierror-invalidtitle',
					wfEscapeWikiText( $params['distancefrompage'] )
				] );
			}
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable T240141
			$coord = GeoData::getPageCoordinates( $title );
			if ( !$coord ) {
				$this->dieWithError(
					[
						'apierror-geodata-noprimarycoord',
						wfEscapeWikiText( $title->getPrefixedText() )
					],
					'no-coordinates'
				);
			}
			if ( $coord->globe != 'earth' ) {
				$this->dieWithError( 'apierror-geodata-notonearth', 'notonearth' );
			}
			return $coord;
		}
		return null;
	}

	/**
	 * @param array $params
	 * @return string
	 */
	public function getCacheMode( $params ) {
		return 'public';
	}

	/**
	 * @inheritDoc
	 */
	public function getAllowedParams() {
		return [
			'limit' => [
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			],
			'continue' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			],
			'prop' => [
				ApiBase::PARAM_TYPE => [ 'type', 'name', 'dim', 'country', 'region', 'globe' ],
				ApiBase::PARAM_DFLT => 'globe',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'primary' => [
				ApiBase::PARAM_TYPE => [ 'primary', 'secondary', 'all' ],
				ApiBase::PARAM_DFLT => 'primary',
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'distancefrompoint' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_HELP_MSG_APPEND => [
					'geodata-api-help-coordinates-format',
				],
			],
			'distancefrompage' => [
				ApiBase::PARAM_TYPE => 'string',
			],
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [
			'action=query&prop=coordinates&titles=Main%20Page'
				=> 'apihelp-query+coordinates-example-1',
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Special:MyLanguage/Extension:GeoData#prop.3Dcoordinates';
	}
}
