--
-- This updates a 1.19.1 database to 1.19.2
--
-- Make changes to Events table
--
alter table Events add column Executed tinyint(3) unsigned not null default 0 after Messaged;
--
-- Make changes to Filters table
--
alter table Filters add column AutoExecute tinytext default '';
--
-- These are optional, but we might as well
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
