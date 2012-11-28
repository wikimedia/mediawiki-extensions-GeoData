<?php


class ApiQueryGeoSearchSphinx extends ApiQueryGeoSearch {
	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	protected function addCoordFilter() {
		global $wgGeoDataSphinxHosts, $wgGeoDataSphinxPort, $wgGeoDataSphinxIndex;

		wfProfileIn( __METHOD__ );
		$search = new SphinxClient();
		$server = GeoData::pickRandom( $wgGeoDataSphinxHosts );
		$search->SetServer( $server, $wgGeoDataSphinxPort );
		$search->SetMatchMode( SPH_MATCH_BOOLEAN );
		$search->SetArrayResult( true );
		$search->SetLimits( 0, 1000, 1000 );
		$search->SetGeoAnchor( 'lat', 'lon', deg2rad( $this->lat ), deg2rad( $this->lon ) );

		$search->SetFilterFloatRange( '@geodist', 0.0, floatval( $this->radius ) );
		$search->SetSortMode( SPH_SORT_ATTR_ASC, '@geodist' );

		// Build a tiled query that uses full-text index to improve search performance
		// equivalent to ( <lat1> || <lat2> || ... ) && ( <lon1> || <lon2> || ... )
		$rect = GeoDataMath::rectAround( $this->lat, $this->lon, $this->radius );
		$vals = array();
		foreach ( self::intRange( $rect["minLat"], $rect["maxLat"], 10 ) as $latInt ) {
			$vals[] = '"LAT' . round( $latInt ) . '"';
		}
		$query = implode( ' | ', $vals );
		$vals = array();
		foreach ( self::intRange( $rect["minLon"], $rect["maxLon"], 10 ) as $lonInt ) {
			$vals[] = '"LON' . round( $lonInt ) . '"';
		}
		$query .= ' ' . implode( ' | ', $vals );

		$result = $search->Query( $query, $wgGeoDataSphinxIndex );
		$err = $search->GetLastError();
		if ( $err ) {
			throw new MWException( "SphinxSearch error: $err" );
		}
		$warning = $search->GetLastWarning();
		if ( $warning ) {
			$this->setWarning( "SphinxSearch warning: $warning" );
		}
		if ( !is_array( $result ) || !isset( $result['matches'] ) ) {
			throw new MWException( 'SphinxClient::Query() returned unexpected result' );
		}
		$ids = array();
		foreach ( $result['matches'] as $match ) {
			$ids[] = $match['id'];
		}
		$this->addWhere( array( 'gt_id' => $ids ) );
		wfProfileOut( __METHOD__ );
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
}
