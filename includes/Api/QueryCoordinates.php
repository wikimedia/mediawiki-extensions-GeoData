<?php

namespace GeoData\Api;

use GeoData\Coord;
use GeoData\GeoData;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiQuery;
use MediaWiki\Api\ApiQueryBase;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Title\Title;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;

/**
 * This query adds an <coordinates> subelement to all pages with the list of coordinated
 * present on those pages.
 */
class QueryCoordinates extends ApiQueryBase {

	public function __construct(
		ApiQuery $query,
		string $moduleName,
		private readonly WikiPageFactory $wikiPageFactory
	) {
		parent::__construct( $query, $moduleName, 'co' );
	}

	public function execute(): void {
		$titles = $this->getPageSet()->getGoodPages();
		if ( !$titles ) {
			return;
		}

		$params = $this->extractRequestParams();
		$this->requireMaxOneParameter( $params, 'distancefrompoint', 'distancefrompage' );

		$this->addTables( 'geo_tags' );
		$this->addFields( [ 'gt_id', 'gt_page_id', 'gt_lat', 'gt_lon', 'gt_primary', 'gt_globe' ] );
		foreach ( $params['prop'] as $prop ) {
			if ( isset( Coord::FIELD_MAPPING[$prop] ) ) {
				$this->addFields( Coord::FIELD_MAPPING[$prop] );
			}
		}
		$this->addWhereFld( 'gt_page_id', array_keys( $titles ) );
		$primary = $params['primary'];
		$this->addWhereIf(
			[ 'gt_primary' => intval( $primary === 'primary' ) ], $primary !== 'all'
		);

		if ( isset( $params['continue'] ) ) {
			$parts = $this->parseContinueParamOrDie( $params['continue'], [ 'int', 'int' ] );
			$this->addWhere( $this->getDB()->buildComparison( '>=', [
				'gt_page_id' => $parts[0],
				'gt_id' => $parts[1],
			] ) );
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
				$column = Coord::FIELD_MAPPING[$prop] ?? null;
				if ( $column && isset( $row->$column ) ) {
					$vals[$prop] = $row->$column;
				}
			}
			$dist = $this->getDistFrom( $params, Coord::newFromRow( $row ) );
			if ( $dist !== null ) {
				$vals['dist'] = round( $dist, 1 );
			}
			$fit = $this->addPageSubItem( $row->gt_page_id, $vals );
			if ( !$fit ) {
				$this->setContinueEnumParameter( 'continue', $row->gt_page_id . '|' . $row->gt_id );
			}
		}
	}

	private function getDistFrom( array $params, Coord $pageCoord ): ?float {
		$fromCoord = null;

		if ( $params['distancefrompoint'] !== null ) {
			$arr = explode( '|', $params['distancefrompoint'] );
			$globe = $pageCoord->getGlobeObj();
			if ( count( $arr ) != 2 || !$globe->coordinatesAreValid( $arr[0], $arr[1] ) ) {
				$this->dieWithError( 'apierror-geodata-badcoord', 'invalid-coord' );
			}
			$fromCoord = new Coord( (float)$arr[0], (float)$arr[1], $globe );
		} elseif ( $params['distancefrompage'] !== null ) {
			$fromCoord = $this->getCoordinatesFromPage( $params['distancefrompage'] );
		}

		return $fromCoord?->sameGlobe( $pageCoord ) ? $fromCoord->distanceTo( $pageCoord ) : null;
	}

	private function getCoordinatesFromPage( string $pageName ): Coord {
		static $coord;

		if ( !$coord ) {
			$title = Title::newFromText( $pageName );
			if ( !$title || !$title->exists() ) {
				$this->dieWithError( [
					'apierror-invalidtitle',
					wfEscapeWikiText( $pageName )
				] );
			}

			$page = $this->wikiPageFactory->newFromTitle( $title );
			$redirectTarget = $page->getRedirectTarget();
			if ( $redirectTarget ) {
				$title = $redirectTarget;
			}
			$coord = GeoData::getPageCoordinates( $title->getArticleID() );
			if ( !$coord ) {
				$this->dieWithError(
					[
						'apierror-geodata-noprimarycoord',
						wfEscapeWikiText( $title->getPrefixedText() )
					],
					'no-coordinates'
				);
			}
		}

		return $coord;
	}

	/** @inheritDoc */
	public function getCacheMode( $params ) {
		return 'public';
	}

	/** @inheritDoc */
	public function getAllowedParams() {
		return [
			'limit' => [
				ParamValidator::PARAM_DEFAULT => 10,
				ParamValidator::PARAM_TYPE => 'limit',
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => ApiBase::LIMIT_BIG1,
				IntegerDef::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			],
			'continue' => [
				ParamValidator::PARAM_TYPE => 'string',
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			],
			'prop' => [
				ParamValidator::PARAM_TYPE => [ 'type', 'name', 'dim', 'country', 'region', 'globe' ],
				ParamValidator::PARAM_DEFAULT => 'globe',
				ParamValidator::PARAM_ISMULTI => true,
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'primary' => [
				ParamValidator::PARAM_TYPE => [ 'primary', 'secondary', 'all' ],
				ParamValidator::PARAM_DEFAULT => 'primary',
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'distancefrompoint' => [
				ParamValidator::PARAM_TYPE => 'string',
				ApiBase::PARAM_HELP_MSG_APPEND => [
					'geodata-api-help-coordinates-format',
				],
			],
			'distancefrompage' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
		];
	}

	/** @inheritDoc */
	protected function getExamplesMessages() {
		return [
			'action=query&prop=coordinates&titles=Main%20Page'
				=> 'apihelp-query+coordinates-example-1',
		];
	}

	/** @inheritDoc */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Special:MyLanguage/Extension:GeoData#prop.3Dcoordinates';
	}
}
