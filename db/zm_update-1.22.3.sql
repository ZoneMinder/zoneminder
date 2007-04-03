--
-- This updates a 1.22.3 database to 1.22.4
--

--
-- Increase the size of the run state definition column
--
alter table States modify column Definition text;

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
