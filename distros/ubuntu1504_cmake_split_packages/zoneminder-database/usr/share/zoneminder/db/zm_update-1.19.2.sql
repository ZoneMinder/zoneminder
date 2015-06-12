--
-- This updates a 1.19.2 database to 1.19.3
--
-- Make changes to Users table
--
alter table Users modify column Password varchar(64) not null default '';
--
-- These are optional, but we might as well
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
