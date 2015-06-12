--
-- This updates a 1.17.1 database to 1.17.2
--
-- Make changes to Zones table
--
alter table Zones change column AlarmThreshold MinPixelThreshold smallint unsigned;
alter table Zones add column MaxPixelThreshold smallint unsigned after MinPixelThreshold;
alter table Events drop column ImagePath;
--
-- These are optional, but we might as well
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
