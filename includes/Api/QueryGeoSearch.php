<?php

namespace GeoData\Api;

use ApiBase;
use ApiPageSet;
use ApiQuery;
use ApiQueryGeneratorBase;
use GeoData\BoundingBox;
use GeoData\Coord;
use GeoData\GeoData;
use GeoData\Globe;
use Title;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use WikiPage;

class QueryGeoSearch extends ApiQueryGeneratorBase {
	private const MIN_RADIUS = 10;
	private const DEFAULT_RADIUS = 500;

	/**
	 * @var Coord The center of search area
	 */
	protected $coord;

	/**
	 * @var BoundingBox Bounding box to search in
	 */
	protected $bbox;

	/**
	 * @var int Search radius
	 */
	protected $radius;

	/**
	 * @var int Id of the page to search around, exclude from results
	 */
	protected $idToExclude;

	/**
	 * @param ApiQuery $query
	 * @param string $moduleName
	 */
	public function __construct( ApiQuery $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'gs' );
	}

	public function execute() {
		$this->run();
	}

	/** @inheritDoc */
	public function getCacheMode( $params ) {
		return 'public';
	}

	/** @inheritDoc */
	public function executeGenerator( $resultPageSet ) {
		$this->run( $resultPageSet );
	}

	/**
	 * @param string $bbox
	 * @param Globe $globe
	 * @return BoundingBox
	 */
	private function parseBbox( string $bbox, Globe $globe ): BoundingBox {
		$parts = explode( '|', $bbox );
		$vals = array_map( 'floatval', $parts );
		if ( count( $parts ) != 4
			// Pass $parts here for extra validation
			|| !$globe->coordinatesAreValid( $parts[0], $parts[1] )
			|| !$globe->coordinatesAreValid( $parts[2], $parts[3] )
			|| $vals[0] <= $vals[2]
		) {
			$this->dieWithError( 'apierror-geodata-invalidbox', 'invalid-bbox' );
		}
		$bbox = new BoundingBox( $vals[0], $vals[1], $vals[2], $vals[3] );
		$area = $bbox->area();
		$maxRadius = $this->getConfig()->get( 'MaxGeoSearchRadius' );
		if ( $area > $maxRadius * $maxRadius * 4 || $area < 100 ) {
			$this->dieWithError( 'apierror-geodata-boxtoobig', 'toobig' );
		}

		return $bbox;
	}

	/**
	 * @param ApiPageSet|null $resultPageSet
	 */
	protected function run( $resultPageSet = null ): void {
		$params = $this->extractRequestParams();

		$globe = new Globe( $params['globe'] );
		$this->requireOnlyOneParameter( $params, 'coord', 'page', 'bbox' );
		if ( isset( $params['coord'] ) ) {
			$arr = explode( '|', $params['coord'] );
			if ( count( $arr ) != 2 || !$globe->coordinatesAreValid( $arr[0], $arr[1] ) ) {
				$this->dieWithError( 'apierror-geodata-badcoord', 'invalid-coord' );
			}
			$this->coord = new Coord( floatval( $arr[0] ), floatval( $arr[1] ), $params['globe'] );
		} elseif ( isset( $params['page'] ) ) {
			$t = Title::newFromText( $params['page'] );
			if ( !$t || !$t->canExist() ) {
				$this->dieWithError( [ 'apierror-invalidtitle', wfEscapeWikiText( $params['page'] ) ] );
			}
			if ( !$t->exists() ) {
				$this->dieWithError(
					[ 'apierror-missingtitle-byname', wfEscapeWikiText( $t->getPrefixedText() ) ], 'missingtitle'
				);
			}

			$pageId = $t->getArticleID();
			$this->coord = GeoData::getPageCoordinates( $pageId );
			if ( !$this->coord ) {
				$this->dieWithError( 'apierror-geodata-nocoord', 'no-coordinates' );
			}
			$this->idToExclude = $pageId;
		} elseif ( isset( $params['bbox'] ) ) {
			$this->bbox = $this->parseBbox( $params['bbox'], $globe );
			// Even when using bbox, we need a center to sort by distance
			$this->coord = $this->bbox->center();
		} else {
			$this->dieDebug( __METHOD__, 'Logic error' );
		}

		// retrieve some fields only if page set needs them
		if ( $resultPageSet === null ) {
			$this->addTables( 'page' );
			$this->addFields( [ 'page_id', 'page_namespace', 'page_title' ] );
		} else {
			$pageQuery = WikiPage::getQueryInfo();
			$this->addTables( $pageQuery['tables'] );
			$this->addFields( $pageQuery['fields'] );
			$this->addJoinConds( $pageQuery['joins'] );
		}
		$this->addWhereFld( 'page_namespace', $params['namespace'] );

		$this->radius = intval( $params['radius'] );

		if ( $resultPageSet === null ) {
			$this->getResult()->addIndexedTagName( [ 'query', $this->getModuleName() ],
				$this->getModulePrefix()
			);
		}
	}

	/** @inheritDoc */
	public function getAllowedParams() {
		$propTypes = [ 'type', 'name', 'dim', 'country', 'region', 'globe' ];
		$primaryTypes = [ 'primary', 'secondary', 'all' ];
		$config = $this->getConfig();
		$maxRadius = $config->get( 'MaxGeoSearchRadius' );
		$defaultGlobe = $config->get( 'DefaultGlobe' );

		$params = [
			'coord' => [
				ParamValidator::PARAM_TYPE => 'string',
				ApiBase::PARAM_HELP_MSG_APPEND => [
					'geodata-api-help-coordinates-format',
				],
			],
			'page' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
			'bbox' => [
				ParamValidator::PARAM_TYPE => 'string',
			],
			'radius' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_DEFAULT => min( self::DEFAULT_RADIUS, $maxRadius ),
				IntegerDef::PARAM_MIN => self::MIN_RADIUS,
				IntegerDef::PARAM_MAX => $maxRadius,
				ApiBase::PARAM_RANGE_ENFORCE => true,
			],
			'maxdim' => [
				ParamValidator::PARAM_TYPE => 'integer',
			],
			'sort' => [
				ParamValidator::PARAM_TYPE => [ 'distance', 'relevance' ],
				ParamValidator::PARAM_DEFAULT => 'distance',
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'limit' => [
				ParamValidator::PARAM_DEFAULT => 10,
				ParamValidator::PARAM_TYPE => 'limit',
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => ApiBase::LIMIT_BIG1,
				IntegerDef::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			],
			// @todo: globe selection disabled until we have a real use case
			'globe' => [
				ParamValidator::PARAM_TYPE => (array)$defaultGlobe,
				ParamValidator::PARAM_DEFAULT => $defaultGlobe,
			],
			'namespace' => [
				ParamValidator::PARAM_TYPE => 'namespace',
				ParamValidator::PARAM_DEFAULT => NS_MAIN,
				ParamValidator::PARAM_ISMULTI => true,
			],
			'prop' => [
				ParamValidator::PARAM_TYPE => $propTypes,
				ParamValidator::PARAM_DEFAULT => 'globe',
				ParamValidator::PARAM_ISMULTI => true,
				ApiBase::PARAM_HELP_MSG_PER_VALUE => array_map( static function ( $i ) use ( $propTypes ) {
					return 'apihelp-query+coordinates-paramvalue-prop-' . $propTypes[$i];
				}, array_flip( $propTypes ) ),
			],
			'primary' => [
				ParamValidator::PARAM_TYPE => $primaryTypes,
				ParamValidator::PARAM_DEFAULT => 'primary',
				ApiBase::PARAM_HELP_MSG_PER_VALUE => array_map( static function ( $i ) use ( $primaryTypes ) {
					return 'apihelp-query+coordinates-paramvalue-primary-' . $primaryTypes[$i];
				}, array_flip( $primaryTypes ) ),
			],
		];
		if ( $config->get( 'GeoDataDebug' ) ) {
			$params['debug'] = [
				ParamValidator::PARAM_TYPE => 'boolean',
			];
		}
		return $params;
	}

	/** @inheritDoc */
	protected function getExamplesMessages() {
		return [
			'action=query&list=geosearch&gsradius=10000&gscoord=37.786971|-122.399677'
				=> 'apihelp-query+geosearch-example-1',
			'action=query&list=geosearch&gsbbox=37.8|-122.3|37.7|-122.4'
				=> 'apihelp-query+geosearch-example-2',
		];
	}

	/** @inheritDoc */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Special:MyLanguage/Extension:GeoData#list.3Dgeosearch';
	}
}
