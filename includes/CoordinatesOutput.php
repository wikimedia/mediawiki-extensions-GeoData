<?php

namespace GeoData;

use MWException;

/**
 * Class that holds output of a parse opertion
 */
class CoordinatesOutput {
	public $limitExceeded = false;
	private $primary = false,
		$secondary = [];

	public function getCount() {
		return count( $this->secondary ) + ( $this->primary ? 1 : 0 );
	}

	public function addPrimary( Coord $c ) {
		if ( $this->primary ) {
			throw new MWException( 'Attempted to insert a second primary coordinate into ' . __CLASS__ );
		}
		$this->primary = $c;
	}

	public function addSecondary( Coord $c ) {
		if ( $c->primary ) {
			throw new MWException( 'Attempted to pass a primary coordinate into ' . __METHOD__ );
		}
		$this->secondary[] = $c;
	}

	public function getPrimary() {
		return $this->primary;
	}

	public function getSecondary() {
		return $this->secondary;
	}

	public function getAll() {
		$res = $this->secondary;
		if ( $this->primary ) {
			array_unshift( $res, $this->primary );
		}
		return $res;
	}
}
