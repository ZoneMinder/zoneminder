--
-- This updates a 1.23.1 database to 1.23.2
--

--
-- Rename typo version of PurgeWhenFull
--
update Filters set Name = "PurgeWhenFull" where Name = "xPurgeWhenFull";

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
