<?php

class SolrUpdateJob extends Job {

	public function __construct() {
		parent::__construct( 'solrUpdate', Title::newMainPage() );
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
