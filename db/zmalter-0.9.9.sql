--
-- This updates a 0.9.9 database to 0.9.10
--
alter table Monitors add column Type enum('Local','Remote') NOT NULL default 'Local' after Name;
alter table Monitors add column Host varchar(64) default NULL after Format;
alter table Monitors add column Port varchar(8) default '80' after Host;
alter table Monitors add column Path varchar(255) default NULL after Port;
alter table Monitors add column MaxFPS decimal( 5, 2) not null default 0.0 after AlarmFrameCount;
alter table Monitors drop column AlarmFrameCount;
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
