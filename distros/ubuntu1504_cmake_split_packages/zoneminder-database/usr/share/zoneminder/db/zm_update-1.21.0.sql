--
-- This updates a 1.21.0 database to 1.21.1
--
-- Make changes to Monitors table
--
alter table Monitors modify column Orientation enum('0','90','180','270','hori','vert') NOT NULL default '0';
alter table Monitors add column AutoStopTimeout decimal(5,2) default NULL after ControlAddress;
--
-- Make changes to Stats table
--
alter table Stats modify column MinBlobSize int(10) unsigned NOT NULL default '0';
alter table Stats modify column MaxBlobSize int(10) unsigned NOT NULL default '0';
--
-- Make changes to Zones table
--
alter table Zones modify column MinBlobPixels int(10) unsigned default NULL;
alter table Zones modify column MaxBlobPixels int(10) unsigned default NULL;
--
-- Add in extra PTZ protocol
--
insert into Controls values (0,'PELCO-P','Local','/usr/local/bin/zmcontrol-pelco-p.pl',1,1,0,1,1,0,0,1,NULL,NULL,NULL,NULL,1,0,3,1,1,0,0,1,NULL,NULL,NULL,NULL,0,NULL,NULL,1,1,0,1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,1,0,1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,20,1,1,1,1,0,0,0,1,1,NULL,NULL,NULL,NULL,1,0,63,1,254,1,NULL,NULL,NULL,NULL,1,0,63,1,254,0,0);
INSERT INTO Controls VALUES (0,'Axis API v2','Remote','/usr/local/bin/zmcontrol-axis-v2.pl',0,0,0,1,0,0,1,0,0,9999,10,2500,0,NULL,NULL,1,1,0,1,0,0,9999,10,2500,0,NULL,NULL,1,1,0,1,0,0,9999,10,2500,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,12,1,1,1,1,1,0,1,0,1,-360,360,1,90,0,NULL,NULL,0,NULL,1,-360,360,1,90,0,NULL,NULL,0,NULL,0,0);
--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
