--
-- This updates a 1.19.4 database to 1.19.5
--
-- Make changes to Monitors table
--
alter table Monitors add column EventPrefix varchar(32) not null default 'Event-' after Orientation;
alter table Monitors add column AlarmFrameCount smallint(5) unsigned not null default '1' after PostEventCount;
--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
