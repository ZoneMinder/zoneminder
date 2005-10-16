--
-- This updates a 1.21.2 database to 1.21.3
--
alter table Monitors add column WebColour varchar(32) NOT NULL default 'red';
update Monitors set WebColour = concat( '#', hex(14*rand()),hex(15*rand()),hex(14*rand()),hex(15*rand()),hex(14*rand()),hex(15*rand()) );
alter table Events add column Height smallint(5) unsigned NOT NULL default '0' after EndTime;
alter table Events add column Width smallint(5) unsigned NOT NULL default '0' after EndTime;
--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
