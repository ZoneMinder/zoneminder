--
-- This updates a 1.22.0 database to 1.22.1
--

--
-- Add support for linked monitors
--
alter table Monitors add column LinkedMonitors varchar(255) NOT NULL default '' after Enabled;

--
-- Revise some defaults and sizes
--
alter table Monitors modify column Device varchar(64) not null default '';
alter table Monitors modify column Host varchar(64) not null default '';
alter table Monitors modify column Port varchar(8) not null default '';
alter table Monitors modify column Path varchar(255) not null default '';
alter table Monitors modify column LabelX smallint(5) unsigned not null default 0;
alter table Monitors modify column LabelY smallint(5) unsigned not null default 0;
alter table Monitors modify column MaxFPS decimal(5,2) default NULL;
update Monitors set MaxFPS = NULL where MaxFPS = 0.00; 

--
-- Add monitor specific alarm max FPS
--
alter table Monitors add column AlarmMaxFPS decimal(5,2) default NULL after MaxFPS;

--
-- Add average pixel difference to stats
--
alter table Stats add column PixelDiff tinyint(3) unsigned NOT NULL default '0' after FrameId;

--
-- Add some new monitor presets
--
INSERT INTO MonitorPresets VALUES ('','Axis IP, 320x240, mpjpeg, B&W','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/mjpg/video.cgi?resolution=320x240&color=0',320,240,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP, 640x480, mpjpeg, B&W','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/mjpg/video.cgi?resolution=640x480&color=0',640,480,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Gadspot IP, jpeg, max 5 FPS','Remote',NULL,NULL,NULL,'<ip-address>',80,'/Jpeg/CamImg.jpg',NULL,NULL,4,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Gadspot IP, mpjpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/GetData.cgi',NULL,NULL,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Gadspot IP, mpjpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/Jpeg/CamImg.jpg',NULL,NULL,4,5.0,0,NULL,NULL,NULL,100,100);

--
-- Modify zone presets a bit
--
UPDATE ZonePresets SET MinPixelThreshold = 60 WHERE Id = 1 OR Id = 4;
UPDATE ZonePresets SET MinPixelThreshold = 40 WHERE Id = 2 OR Id = 5;
UPDATE ZonePresets SET MinPixelThreshold = 20 WHERE Id = 3 OR Id = 6;

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
