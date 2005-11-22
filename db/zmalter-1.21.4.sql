--
-- This updates a 1.21.3 database to 1.21.4
--
alter table Monitors add column DefaultRate smallint unsigned not null default 100 after ReturnDelay;
alter table Monitors modify column DefaultRate smallint unsigned not null default 100;
--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
