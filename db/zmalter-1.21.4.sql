--
-- This updates a 1.21.3 database to 1.21.4
--
alter table Monitors add column DefaultRate smallint unsigned not null default 100 after ReturnDelay;
alter table Monitors modify column DefaultRate smallint unsigned not null default 100;
--
alter table Events add column Videoed tinyint unsigned not null default 0 after Archived;
alter table Filters add column AutoVideo tinyint unsigned not null default 0 after AutoArchive;
alter table Filters add column Temp tinyint unsigned not null default 0;
update Filters set Temp = AutoDelete;
alter table Filters drop column AutoDelete;
alter table Filters change column Temp AutoDelete tinyint unsigned not null default 0;
alter table Filters change column AutoExecute AutoExecuteCmd tinytext;
alter table Filters add column AutoExecute tinyint unsigned not null default 0 after AutoMessage;
update Filters set AutoExecute =  if(isnull(AutoExecuteCmd)||AutoExecuteCmd='', 0, 1 );
--
alter table Zones add column NumCoords tinyint(3) unsigned NOT NULL default '0' after Units;
alter table Zones add column Coords tinytext NOT NULL after Points;
alter table Zones add column Area int(10) unsigned not null default 0 after Coordinates;
alter table Zones modify column AlarmRGB int(10) unsigned default '0';
--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
