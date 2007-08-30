--
-- This updates a 1.22.1 database to 1.22.2
--

--
-- Add missing Zone Preset
--
replace into ZonePresets values (6,'Best, high sensitivity','Active','Percent','Blobs',20,NULL,8,NULL,3,3,6,NULL,5,NULL,1,NULL);

--
-- Change control command to protocol module
--
alter table Controls add column Protocol varchar(32) after Command;
update Controls set Protocol = "PelcoD" where Command = "zmcontrol-pelco-d.pl";
update Controls set Protocol = "PelcoP" where Command = "zmcontrol-pelco-p.pl";
update Controls set Protocol = "Visca" where Command = "zmcontrol-visca.pl";
update Controls set Protocol = "PanasonicIP" where Command = "zmcontrol-panasonic-ip.pl";
update Controls set Protocol = "AxisV2" where Command = "zmcontrol-axis-v2.pl";
update Controls set Protocol = "Ncs370" where Command = "zmcontrol-ncs370.pl";
alter table Controls drop column Command;

--
-- Change control command to protocol module
--
alter table Controls add column Protocol varchar(32) after Command;
update Controls set Protocol = "PelcoD" where Command like "%zmcontrol-pelco-d.pl";
update Controls set Protocol = "PelcoP" where Command like "%zmcontrol-pelco-p.pl";
update Controls set Protocol = "Visca" where Command like "%zmcontrol-visca.pl";
update Controls set Protocol = "PanasonicIP" where Command like "%zmcontrol-panasonic-ip.pl";
update Controls set Protocol = "AxisV2" where Command like "%zmcontrol-axis-v2.pl";
update Controls set Protocol = "Ncs370" where Command like "%zmcontrol-ncs370.pl";
alter table Controls drop column Command;

--
-- Remove redundant Zone columns
--
alter table Zones drop column LoX;
alter table Zones drop column HiX;
alter table Zones drop column LoY;
alter table Zones drop column HiY;

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
