--
-- This updates a 0.9.16 database to 0.9.17
--
-- Make changes to Users table
--
alter table Users add column Language varchar(8) not null default "" after Password;
--
-- These are optional, but we might as well
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
