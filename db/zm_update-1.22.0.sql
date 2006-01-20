--
-- This updates a 1.22.0 database to 1.22.1
--
--
-- Modify zone presets a bit
--
UPDATE ZonePresets SET MinPixelThreshold = 60 WHERE Id = 1 OR Id = 4;
UPDATE ZonePresets SET MinPixelThreshold = 40 WHERE Id = 2 OR Id = 5;
UPDATE ZonePresets SET MinPixelThreshold = 20 WHERE Id = 3 OR Id = 6;

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
