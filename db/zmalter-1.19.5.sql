--
-- This updates a 1.19.5 database to 1.19.6
--
-- Add a new index to the Events table
--
alter table Events add index (Frames);
--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
