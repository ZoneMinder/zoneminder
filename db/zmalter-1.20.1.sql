--
-- This updates a 1.20.0 database to 1.20.1
--
-- Make changes to Monitors table
--
alter table Monitors add column Controllable tinyint(3) unsigned NOT NULL default '0';
alter table Monitors add column ControlId int(10) unsigned NOT NULL default '0';
alter table Monitors add column ControlDevice varchar(255) default NULL;
alter table Monitors add column ControlAddress varchar(255) default NULL;
--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
