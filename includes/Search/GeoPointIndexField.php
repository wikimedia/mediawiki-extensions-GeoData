<?php

namespace GeoData\Search;

use CirrusSearch\Search\CirrusIndexField;
use CirrusSearch\SearchConfig;

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
}
