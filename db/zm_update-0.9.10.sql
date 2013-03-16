--
-- This updates a 0.9.10 database to 0.9.11
--
alter table Monitors change column Colours Palette tinyint(3) unsigned NOT NULL default '1';
update Monitors set Palette = 1 where Palette = 8;
update Monitors set Palette = 4 where Palette = 24;
alter table Zones modify column Type enum('Active','Inclusive','Exclusive','Preclusive','Inactive') not null default 'Active';
alter table Filters add column AutoArchive tinyint unsigned not null default 0 after Query;
-- These are optional, it just seemed a good time...
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
