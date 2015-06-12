--
-- This updates a 0.9.11 database to 0.9.12
--
alter table Monitors add column Orientation enum('0','90','180','270') not null default '0' after Palette; 
-- These are optional, it just seemed a good time...
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
