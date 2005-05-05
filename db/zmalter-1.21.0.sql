--
-- This updates a 1.21.0 database to 1.21.1
--
-- Make changes to Monitors table
--
alter table Monitors modify column Orientation enum('0','90','180','270','hori','vert') NOT NULL default '0';
--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
