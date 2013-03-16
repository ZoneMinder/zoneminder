--
-- This updates a 1.22.3 database to 1.23.0
--

--
-- Add a column for buffer replay streams
--
alter table Monitors add column `StreamReplayBuffer` int(10) unsigned NOT NULL default '1000' after PostEventCount;

--
-- Change the default timestamp format
--
alter table Monitors modify column `LabelFormat` varchar(64) NOT NULL default '%N - %y/%m/%d %H:%M:%S';

--
-- Add a column for signal check colour
--
alter table Monitors add column `SignalCheckColour` varchar(32) NOT NULL default '#0100BE' after DefaultScale;

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
-- Change control command to protocol module
--
alter table Controls add column Protocol varchar(32) after Command;
update Controls set Protocol = "PelcoD" where Command like "%zmcontrol-pelco-d.pl";
update Controls set Protocol = "PelcoP" where Command like "%zmcontrol-pelco-p.pl";
update Controls set Protocol = "Visca" where Command like "%zmcontrol-visca.pl";
update Controls set Protocol = "PanasonicIP" where Command like "%zmcontrol-panasonic-ip.pl";
update Controls set Protocol = "AxisV2" where Command like "%zmcontrol-axis-v2.pl";
update Controls set Protocol = "Ncs370" where Command like "%zmcontrol-ncs370.pl";
update Controls set Protocol = "VclTP" where Command like "%zmcontrol-vcltp.pl";
alter table Controls drop column Command;

--
-- Drop some duplicate/redundant indices
--
alter table Config drop index `UC_Name`;
alter table Controls drop index `UC_Id`;
alter table Devices drop index `UC_Id`;
alter table Events drop index `UC_Id`;
alter table Frames drop index `UC_Id`;
alter table Users drop index `UC_Id`;
alter table ZonePresets drop index `UC_Id`;
alter table Zones drop index `UC_Id`;
alter table Events drop primary key;
alter table Events add primary key ( `Id`, `MonitorId` );
alter table Events drop index `Id`;
alter table Frames drop column `Id`;
alter table Frames drop index `EventId`;
alter table Frames add primary key ( `EventId`, `FrameId` );

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
