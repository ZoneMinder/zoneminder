--
-- This updates a 1.21.0 database to 1.21.1
--
-- Make changes to Monitors table
--
alter table Monitors modify column Orientation enum('0','90','180','270','hori','vert') NOT NULL default '0';
--
-- Make changes to Stats table
--
alter table Stats modify column MinBlobSize int(10) unsigned NOT NULL default '0';
alter table Stats modify column MaxBlobSize int(10) unsigned NOT NULL default '0';
--
-- Make changes to Zones table
--
alter table Zones modify column MinBlobPixels int(10) unsigned default NULL;
alter table Zones modify column MaxBlobPixels int(10) unsigned default NULL;
--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
