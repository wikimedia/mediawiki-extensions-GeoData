<?php

namespace GeoData\Api;

use ApiBase;
use ApiQuery;
use ApiQueryBase;
use GeoData\Coord;
use GeoData\GeoData;
use GeoData\Globe;
use GeoData\Math;
use MediaWiki\Page\WikiPageFactory;
use MWException;
use Title;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;

/**
 * This query adds an <coordinates> subelement to all pages with the list of coordinated
 * present on those pages.
 */
class QueryCoordinates extends ApiQueryBase {

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/**
	 * @param ApiQuery $query
	 * @param string $moduleName
	 * @param WikiPageFactory $wikiPageFactory
	 */
	public function __construct( ApiQuery $query, $moduleName, WikiPageFactory $wikiPageFactory ) {
		parent::__construct( $query, $moduleName, 'co' );
		$this->wikiPageFactory = $wikiPageFactory;
	}

	public function execute(): void {
		$titles = $this->getPageSet()->getGoodTitles();
		if ( $titles === [] ) {
			return;
		}

		$params = $this->extractRequestParams();
		$from = $this->getFromCoord( $params );
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
	private function getFromCoord( array $params ): ?Coord {
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
			if ( !$title || !$title->exists() ) {
				$this->dieWithError( [
					'apierror-invalidtitle',
					wfEscapeWikiText( $params['distancefrompage'] )
				] );
			}
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable T240141
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
			if ( $coord->globe != 'earth' ) {
				$this->dieWithError( 'apierror-geodata-notonearth', 'notonearth' );
			}
			return $coord;
		}
		return null;
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
