--
-- This updates a 1.21.3 database to 1.21.4
--
alter table Monitors add column WebColour varchar(32) not null default 'red';
update Monitors set WebColour = concat( '#', hex(14*rand()),hex(15*rand()),hex(14*rand()),hex(15*rand()),hex(14*rand()),hex(15*rand()) );
alter table Monitors add column Sequence smallint unsigned;
alter table Monitors modify column Device tinytext;
update Monitors set Device = concat( "/dev/video", Device );
update Monitors set Device = NULL where Type = "Remote";
alter table Monitors add column DefaultScale smallint unsigned after ReturnDelay;
alter table Monitors modify column Type enum('Local','Remote','File') NOT NULL default 'Local';
alter table Events add column Height smallint(5) unsigned not null default '0' after EndTime;
alter table Events add column Width smallint(5) unsigned not null default '0' after EndTime;
alter table Users add column Control enum('None','View','Edit') NOT NULL default 'None' after Events;
update Users set Control = System;
alter table Users add column MaxBandwidth varchar(16) not null default '' after System;
--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
