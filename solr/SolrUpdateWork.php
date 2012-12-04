<?php

class SolrUpdateWork extends PoolCounterWork {
	private $maint;

	public function __construct( SolrUpdate $maint ) {
		parent::__construct( 'solrUpdate', '*' );
		$this->maint = $maint;
	}

	function doWork() {
		$this->maint->safeExecute();
		return true;
	}
}
