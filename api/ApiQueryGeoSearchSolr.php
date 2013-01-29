<?php

class ApiQueryGeoSearchSolr extends ApiQueryGeoSearch {
	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	/**
	 * @param ApiPageSet $resultPageSet
	 */
	protected function run( $resultPageSet = null ) {
		wfProfileIn( __METHOD__ );
		parent::run( $resultPageSet );

		try {
			$params = $this->extractRequestParams();

			$solr = SolrGeoData::newClient();
			$query = $solr->createSelect();
			$helper = $query->getHelper();

			// @todo: props
			$query->setQueryDefaultOperator( 'AND' );
			$query->createFilterQuery( 'wiki' )->setQuery( 'wiki:' . wfWikiID() ); // Only Earth is supported
			$query->createFilterQuery( 'globe' )->setQuery( 'globe:earth' ); // Only Earth is supported
			if ( isset( $params['maxdim'] ) ) {
				$query->addFilterQuery( "dim:[* TO {$params['maxdim']}]" );
			}
			$primary = $params['primary'];
			if ( $primary !== 'all' ) {
				$query->createFilterQuery( 'primary' )->setQuery( 'primary:' . intval( $primary === 'primary' ) );
			}
			$query->createFilterQuery( 'coord' )->setQuery( $helper->geofilt( $this->lat, $this->lon, 'coord', $this->radius / 1000 ) );
			$query->addSort( $helper->geodist( $this->lat, $this->lon, 'coord' ), Solarium_Query_Select::SORT_ASC );

			$limit = $params['limit'];
			$query->setRows( $limit + ( $this->idToExclude ? 1 : 0 ) ); // +1 in case we need to exclude a page

			wfProfileIn( __METHOD__ . '-solr' );
			$docs = $solr->select( $query );
			wfProfileOut( __METHOD__ . '-solr' );
			$mapping = array();
			foreach ( $docs as $doc ) {
				$id = $doc->page_id;
				if ( !isset( $mapping[$id] ) && $id != $this->idToExclude ) {
					$mapping[$id] = $doc;
				}
			}

			if ( !count( $mapping ) ) {
				wfProfileOut( __METHOD__ );
				return; // No results, no point in doing anything else
			}
			$this->addWhere( array( 'page_id' => array_keys( $mapping ) ) );

			wfProfileIn( __METHOD__ . '-sql' );
			$res = $this->select( __METHOD__ );
			wfProfileOut( __METHOD__ . '-sql' );

			$result = $this->getResult();
			$rows = array();
			foreach ( $res as $row ) {
				$rows[$row->page_id] = $row;
			}

			foreach ( $mapping as $id => $doc ) {
				if ( !$limit-- ) {
					break;
				}
				if ( !isset( $rows[$id] ) ) {
					continue;
				}
				$row = $rows[$id];
				if ( is_null( $resultPageSet ) ) {
					$title = Title::newFromRow( $row );
					list( $lat, $lon ) = explode( ',', $doc->coord );
					$vals = array(
						'pageid' => intval( $row->page_id ),
						'ns' => intval( $title->getNamespace() ),
						'title' => $title->getPrefixedText(),
						'lat' => floatval( $lat ),
						'lon' => floatval( $lon ),
						'dist' => round( GeoDataMath::distance( $lat, $lon, $this->lat, $this->lon ), 1 ),
					);

					if ( $doc->primary ) {
						$vals['primary'] = '';
					}
					foreach( $params['prop'] as $prop ) {
						$vals[$prop] = $doc->$prop;
					}
					$fit = $result->addValue( array( 'query', $this->getModuleName() ), null, $vals );
					if ( !$fit ) {
						break;
					}
				} else {
					$resultPageSet->processDbRow( $row );
				}
			}
		} catch ( Solarium_Exception $e ) {
			throw new MWException( get_class( $e ) . " at {$e->getFile()}, line {$e->getLine()}: {$e->getMessage()}", 0, $e );
		}
		wfProfileOut( __METHOD__ );
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
}
