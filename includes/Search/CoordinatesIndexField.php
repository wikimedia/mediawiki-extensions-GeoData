<?php

namespace GeoData\Search;

use CirrusSearch\Search\NestedIndexField;
use CirrusSearch\SearchConfig;
use SearchEngine;
use SearchIndexField;

/**
 * Nested type for CirrusSearch mapping
 */
class CoordinatesIndexField extends NestedIndexField {
	/**
	 * @param string $name name of the field
	 * @param SearchConfig $config CirrusSearch config
	 */
	public function __construct( $name, SearchConfig $config ) {
		parent::__construct( $name, $this->typeName, $config );
	}

	/**
	 * Builds a new CoordinatesIndexField nested field
	 * @param string $name field name
	 * @param SearchConfig $config
	 * @param SearchEngine $engine
	 * @return CoordinatesIndexField
	 */
	public static function build( $name, SearchConfig $config, SearchEngine $engine ) {
		$nested = new self( $name, $config );
		$nested->addSubfield( 'coord', new GeoPointIndexField( 'coord', $config ) );
		// Setting analyzer to keyword is similar to index => not_analyzed
		$keywords = [ 'globe', 'type', 'country', 'region' ];
		foreach ( $keywords as $keyword ) {
			$nested->addSubfield( $keyword, $engine->makeSearchFieldMapping( $keyword,
					SearchIndexField::INDEX_TYPE_KEYWORD ) );
		}
		$nested->addSubfield( 'primary', $engine->makeSearchFieldMapping( 'primary',
			SearchIndexField::INDEX_TYPE_BOOL ) );
		$nested->addSubfield( 'dim', $engine->makeSearchFieldMapping( 'dim',
			SearchIndexField::INDEX_TYPE_NUMBER ) );
		$name = $engine->makeSearchFieldMapping( 'name', SearchIndexField::INDEX_TYPE_TEXT );
		$name->setFlag( SearchIndexField::FLAG_NO_INDEX );
		$nested->addSubfield( 'name', $name );
		return $nested;
	}
}
