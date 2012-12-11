-- Some tables were created on WMF using MyISAM
-- This script fixes them

TRUNCATE TABLE /*_*/geo_killlist;

ALTER TABLE /*_*/geo_killlist ENGINE=InnoDB;

ALTER TABLE /*_*/geo_updates ENGINE=InnoDB;
