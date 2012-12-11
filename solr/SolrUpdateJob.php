<?php

class SolrUpdateJob extends Job {

	public function __construct( $title, $params = array(), $id = 0 ) {
		parent::__construct( 'solrUpdate', Title::newMainPage(), $params, $id );
		$this->removeDuplicates = true;
	}

	/**
	 * Run the job
	 * @return boolean success
	 */
	public function run() {
		$maint = new SolrUpdate();
		$maint->enableJobMode();
		$maint->execute();
	}
}
