--
-- This updates a 1.20.0 database to 1.20.1
--
-- Make changes to Users table
--
alter table Users modify column Username varchar(32) BINARY NOT NULL default '';
--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
