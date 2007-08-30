--
-- This updates a 1.22.1 database to 1.22.2
--

--
-- Add missing Zone Preset
--
replace into ZonePresets values (6,'Best, high sensitivity','Active','Percent','Blobs',20,NULL,8,NULL,3,3,6,NULL,5,NULL,1,NULL);

--
-- Remove redundant Zone columns
--
alter table Zones drop column LoX;
alter table Zones drop column HiX;
alter table Zones drop column LoY;
alter table Zones drop column HiY;

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
