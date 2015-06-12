--
-- This updates a 1.24.0 database to 1.24.1
--

--
-- No database changes
--

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
