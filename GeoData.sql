-- SQL schema for GeoData extension

-- Stores information about geographical coordinates in articles
CREATE TABLE /*_*/geo_tags (
	-- Tag id, needed for selective replacement and paging
	gt_id int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
	-- page_id
	gt_page_id int unsigned NOT NULL,
	-- Name of planet or other astronomic body on which the coordinates reside
	gt_globe varchar(32) NOT NULL,
	-- Whether this coordinate is primary (defines the principal location of article subject)
	-- or secondary (just mentioned in text)
	gt_primary bool NOT NULL,
	-- Latitude of the point in degrees
	gt_lat float NOT NULL,
	-- Longitude of the point in degrees
	gt_lon float NOT NULL,
	-- Approximate viewing radius in meters, gives an idea how large the object is
	gt_dim int NULL,
	-- Type of the point
	gt_type varchar(32) NULL,
	-- Point name on the map
	gt_name varchar(255) binary NULL,
	-- Two character ISO 3166-1 alpha-2 country code
	gt_country char(2) NULL,
	-- Second part of ISO 3166-2 region code, up to 3 alphanumeric chars
	gt_region varchar(3) NULL
)/*$wgDBTableOptions*/;

-- @todo
CREATE INDEX /*i*/gt_page_id ON /*_*/geo_tags ( gt_page_id );
CREATE INDEX /*i*/gt_lat_lon ON /*_*/geo_tags ( gt_lat, gt_lon );

DELIMITER //
-- Calculates distance in meters between two points
-- See https://en.wikipedia.org/wiki/Haversine_formula
CREATE FUNCTION /*_*/gd_distance( lat1 double, lon1 double, lat2 double, lon2 double )
	RETURNS double DETERMINISTIC
BEGIN
	SET lat1 = radians( lat1 );
	SET lon1 = radians( lon1 );
	SET lat2 = radians( lat2 );
	SET lon2 = radians( lon2 );
	SET @sin1 = sin( ( lat2 - lat1 ) / 2 );
	SET @sin2 = sin( ( lon2 - lon1 ) / 2 );
	RETURN 2 * 6371010 * asin( sqrt( @sin1 * @sin1 + cos( lat1 ) * cos( lat2 ) * @sin2 * @sin2 ) );
END//

DELIMITER ;
