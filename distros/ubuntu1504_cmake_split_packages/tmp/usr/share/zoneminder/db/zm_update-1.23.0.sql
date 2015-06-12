--
-- This updates a 1.23.0 database to 1.23.1
--

--
-- Change protocol field slightly
--
alter table Controls modify Protocol varchar(64);

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
