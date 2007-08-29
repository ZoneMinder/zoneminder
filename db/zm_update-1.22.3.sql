--
-- This updates a 1.22.3 database to 1.22.4
--

--
-- Add a column for buffer replay streams
--
alter table Monitors add column `StreamReplayBuffer` int(10) unsigned NOT NULL default '1000' after PostEventCount;

--
-- Increase the size of the run state definition column
--
alter table States modify column Definition text;

--
-- Add overload shutout to zones and presets
--
alter table Zones add column OverloadFrames smallint(5) unsigned NOT NULL default '0' after MaxBlobs;
alter table ZonePresets add column OverloadFrames smallint(5) unsigned NOT NULL default '0' after MaxBlobs;

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
