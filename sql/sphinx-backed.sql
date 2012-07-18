-- SQL schema for GeoData extension, Sphinx-aware

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
	gt_region varchar(3) NULL,
	-- Last change timestamp
	gt_touched timestamp NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)/*$wgDBTableOptions*/;

CREATE INDEX /*i*/gt_page_primary ON /*_*/geo_tags ( gt_page_id, gt_primary );
CREATE INDEX /*i*/gt_page_id_id ON /*_*/geo_tags ( gt_page_id, gt_id );
CREATE INDEX /*i*/gt_touched ON /*_*/geo_tags ( gt_touched );

-- Stores Sphinx search kill-list (ids of records deleted from geo_tags)
CREATE TABLE /*_*/geo_killist (
	-- gt_id of a row deleted from geo_tags
	gk_id int unsigned NOT NULL PRIMARY KEY,
	-- Last change timestamp
	gk_touched timestamp NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)/*$wgTableOptions*/;

CREATE INDEX /*i*/gk_touched ON /*_*/geo_killist ( gk_touched );

-- Stores information about the last index update time
CREATE TABLE /*_*/geo_updates (
	gu_wiki varchar(64) NOT NULL PRIMARY KEY,
	gu_last_update timestamp NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)/*$wgTableOptions*/;
