<?php
namespace GeoData;

use CirrusSearch\Search\CirrusIndexField;
use CirrusSearch\SearchConfig;
use SearchEngine;

/**
 * GeoPoint type for CirrusSearch mapping
 */
class GeoPointIndexField extends CirrusIndexField {
	/**
	 * @var string
	 */
	protected $typeName = 'geo_point';


	/**
	 * @param string $name name of the field
	 * @param SearchConfig $config CirrusSearch config
	 */
	public function __construct( $name, SearchConfig $config ) {
		parent::__construct( $name, $this->typeName, $config );
	}

	/**
	 * @param SearchConfig $engine
	 * @return array elasticsearch mapping
	 */
	public function getMapping( SearchEngine $engine ) {
		$fields = parent::getMapping( $engine );
		// Used by the geo distance query to run bounding box
		// optimization query
		// @fixme: lat_lon will be removed in elastic 5x
		$fields['lat_lon'] = true;
		return $fields;
	}
}

