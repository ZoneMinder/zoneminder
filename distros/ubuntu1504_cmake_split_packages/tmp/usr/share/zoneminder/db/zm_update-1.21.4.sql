--
-- This updates a 1.21.4 database to 1.22.0
--
alter table Monitors change column RunMode Enabled tinyint(3) unsigned NOT NULL default '1';
alter table Monitors add column DefaultRate smallint unsigned not null default 100 after ReturnDelay;
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
alter table Zones add column Coords tinytext NOT NULL after NumCoords;
alter table Zones add column Area int(10) unsigned not null default 0 after Coords;
alter table Zones modify column AlarmRGB int(10) unsigned default '0';
alter table Zones add index MonitorId (MonitorId);  
--
insert into Controls values ('','Neu-Fusion NCS370','Remote','zmcontrol-ncs370.pl',0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,24,1,0,1,1,0,0,0,1,1,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,1,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,0,0);

--
-- Table structure for table `MonitorPresets`
--
CREATE TABLE MonitorPresets (
  Id int(10) unsigned NOT NULL auto_increment,
  Name varchar(64) NOT NULL, 
  Type enum('Local','Remote','File') NOT NULL default 'Local',
  Device tinytext,
  Channel varchar(32) default NULL,
  Format varchar(32) default NULL,
  Host varchar(64) default NULL,
  Port varchar(8) default NULL,
  Path varchar(255) default NULL,
  Width smallint(5) unsigned default NULL,
  Height smallint(5) unsigned default NULL,
  Palette tinyint(3) unsigned default NULL,
  MaxFPS decimal(5,2) default NULL,
  Controllable tinyint(3) unsigned NOT NULL default '0',
  ControlId varchar(16) default NULL,
  ControlDevice varchar(255) default NULL,
  ControlAddress varchar(255) default NULL,
  DefaultRate smallint(5) unsigned NOT NULL default '100',
  DefaultScale smallint(5) unsigned NOT NULL default '100',
  PRIMARY KEY  (Id)
) TYPE=MyISAM;

--
-- Dumping data for table `MonitorPresets`
--
INSERT INTO MonitorPresets VALUES ('','BTTV Video, PAL, 320x240','Local','/dev/video<?>','<?>','0',NULL,NULL,NULL,320,240,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video, PAL, 320x240, max 5 FPS','Local','/dev/video<?>','<?>','0',NULL,NULL,NULL,320,240,4,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video, PAL, 640x480','Local','/dev/video<?>','<?>','0',NULL,NULL,NULL,640,480,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video, PAL, 640x480, max 5 FPS','Local','/dev/video<?>','<?>','0',NULL,NULL,NULL,640,480,4,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video, NTSC, 320x240','Local','/dev/video<?>','<?>','1',NULL,NULL,NULL,320,240,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video, NTSC, 320x240, max 5 FPS','Local','/dev/video<?>','<?>','1',NULL,NULL,NULL,320,240,4,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video, NTSC, 640x480','Local','/dev/video<?>','<?>','1',NULL,NULL,NULL,640,480,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','BTTV Video, NTSC, 640x480, max 5 FPS','Local','/dev/video<?>','<?>','1',NULL,NULL,NULL,640,480,4,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP, 320x240, mpjpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/mjpg/video.cgi?resolution=320x240',320,240,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP, 320x240, mpjpeg, max 5 FPS','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/mjpg/video.cgi?resolution=320x240&req_fps=5',320,240,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP, 320x240, jpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/jpg/image.cgi?resolution=320x240',320,240,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP, 320x240, jpeg, max 5 FPS','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/jpg/image.cgi?resolution=320x240',320,240,4,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP, 640x480, mpjpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/mjpg/video.cgi?resolution=640x480',640,480,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP, 640x480, mpjpeg, max 5 FPS','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/mjpg/video.cgi?resolution=640x480&req_fps=5',640,480,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP, 640x480, jpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/jpg/image.cgi?resolution=640x480',640,480,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP, 640x480, jpeg, max 5 FPS','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/jpg/image.cgi?resolution=640x480',640,480,4,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP PTZ, 320x240, mpjpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/mjpg/video.cgi?resolution=320x240',320,240,4,NULL,1,4,NULL,'<ip-address>:<port>',100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP PTZ, 320x240, mpjpeg, max 5 FPS','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/mjpg/video.cgi?resolution=320x240&req_fps=5',320,240,4,NULL,1,4,NULL,'<ip-address>:<port>',100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP PTZ, 320x240, jpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/jpg/image.cgi?resolution=320x240',320,240,4,NULL,1,4,NULL,'<ip-address>:<port>',100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP PTZ, 320x240, jpeg, max 5 FPS','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/jpg/image.cgi?resolution=320x240',320,240,4,5.0,1,4,NULL,'<ip-address>:<port>',100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP PTZ, 640x480, mpjpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/mjpg/video.cgi?resolution=640x480',640,480,4,NULL,1,4,NULL,'<ip-address>:<port>',100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP PTZ, 640x480, mpjpeg, max 5 FPS','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/mjpg/video.cgi?resolution=640x480&req_fps=5',640,480,4,NULL,1,4,NULL,'<ip-address>:<port>',100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP PTZ, 640x480, jpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/jpg/image.cgi?resolution=640x480',640,480,4,NULL,1,4,NULL,'<ip-address>:<port>',100,100);
INSERT INTO MonitorPresets VALUES ('','Axis IP PTZ, 640x480, jpeg, max 5 FPS','Remote',NULL,NULL,NULL,'<ip-address>',80,'/axis-cgi/jpg/image.cgi?resolution=640x480',640,480,4,5.0,1,4,NULL,'<ip-address>:<port>',100,100);
INSERT INTO MonitorPresets VALUES ('','Panasonic IP, 320x240, mpjpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/nphMotionJpeg?Resolution=320x240&Quality=Standard',320,240,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Panasonic IP, 320x240, jpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/SnapshotJPEG?Resolution=320x240&Quality=Standard',320,240,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Panasonic IP, 320x240, jpeg, max 5 FPS','Remote',NULL,NULL,NULL,'<ip-address>',80,'/SnapshotJPEG?Resolution=320x240&Quality=Standard',320,240,4,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Panasonic IP, 640x480, mpjpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/nphMotionJpeg?Resolution=640x480&Quality=Standard',640,480,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Panasonic IP, 640x480, jpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/SnapshotJPEG?Resolution=640x480&Quality=Standard',640,480,4,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Panasonic IP, 640x480, jpeg, max 5 FPS','Remote',NULL,NULL,NULL,'<ip-address>',80,'/SnapshotJPEG?Resolution=640x480&Quality=Standard',640,480,4,5.0,0,NULL,NULL,NULL,100,100);
INSERT INTO MonitorPresets VALUES ('','Panasonic IP PTZ, 320x240, mpjpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/nphMotionJpeg?Resolution=320x240&Quality=Standard',320,240,4,NULL,1,5,NULL,'<ip-address>:<port>',100,100);
INSERT INTO MonitorPresets VALUES ('','Panasonic IP PTZ, 320x240, jpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/SnapshotJPEG?Resolution=320x240&Quality=Standard',320,240,4,NULL,1,5,NULL,'<ip-address>:<port>',100,100);
INSERT INTO MonitorPresets VALUES ('','Panasonic IP PTZ, 320x240, jpeg, max 5 FPS','Remote',NULL,NULL,NULL,'<ip-address>',80,'/SnapshotJPEG?Resolution=320x240&Quality=Standard',320,240,4,5.0,1,5,NULL,'<ip-address>:<port>',100,100);
INSERT INTO MonitorPresets VALUES ('','Panasonic IP PTZ, 640x480, mpjpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/nphMotionJpeg?Resolution=640x480&Quality=Standard',640,480,4,NULL,1,5,NULL,'<ip-address>:<port>',100,100);
INSERT INTO MonitorPresets VALUES ('','Panasonic IP PTZ, 640x480, jpeg','Remote',NULL,NULL,NULL,'<ip-address>',80,'/SnapshotJPEG?Resolution=640x480&Quality=Standard',640,480,4,NULL,1,5,NULL,'<ip-address>:<port>',100,100);
INSERT INTO MonitorPresets VALUES ('','Panasonic IP PTZ, 640x480, jpeg, max 5 FPS','Remote',NULL,NULL,NULL,'<ip-address>',80,'/SnapshotJPEG?Resolution=640x480&Quality=Standard',640,480,4,5.0,1,5,NULL,'<ip-address>:<port>',100,100);

--
-- Table structure for table `ZonePresets`
--
CREATE TABLE ZonePresets (
  Id int(10) unsigned NOT NULL auto_increment,
  Name varchar(64) NOT NULL default '',
  Type enum('Active','Inclusive','Exclusive','Preclusive','Inactive') NOT NULL default 'Active',
  Units enum('Pixels','Percent') NOT NULL default 'Pixels',
  CheckMethod enum('AlarmedPixels','FilteredPixels','Blobs') NOT NULL default 'Blobs',
  MinPixelThreshold smallint(5) unsigned default NULL,
  MaxPixelThreshold smallint(5) unsigned default NULL,
  MinAlarmPixels int(10) unsigned default NULL,
  MaxAlarmPixels int(10) unsigned default NULL,
  FilterX tinyint(3) unsigned default NULL,
  FilterY tinyint(3) unsigned default NULL,
  MinFilterPixels int(10) unsigned default NULL,
  MaxFilterPixels int(10) unsigned default NULL,
  MinBlobPixels int(10) unsigned default NULL,
  MaxBlobPixels int(10) unsigned default NULL,
  MinBlobs smallint(5) unsigned default NULL,
  MaxBlobs smallint(5) unsigned default NULL,
  PRIMARY KEY  (Id),
  UNIQUE KEY UC_Id (Id)
) TYPE=MyISAM;

--
-- Dumping data for table `ZonePresets`
--
INSERT INTO ZonePresets VALUES (1,'Fast, low sensitivity','Active','Percent','AlarmedPixels',25,NULL,20,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO ZonePresets VALUES (2,'Fast, medium sensitivity','Active','Percent','AlarmedPixels',15,NULL,10,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO ZonePresets VALUES (3,'Fast, high sensitivity','Active','Percent','AlarmedPixels',10,NULL,5,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
INSERT INTO ZonePresets VALUES (4,'Best, low sensitivity','Active','Percent','Blobs',25,NULL,36,NULL,7,7,24,NULL,20,NULL,1,NULL);
INSERT INTO ZonePresets VALUES (5,'Best, medium sensitivity','Active','Percent','Blobs',15,NULL,16,NULL,5,5,12,NULL,10,NULL,1,NULL);
INSERT INTO ZonePresets VALUES (6,'Best, high sensitivity','Active','Percent','Blobs',10,NULL,8,NULL,3,3,6,NULL,5,NULL,1,NULL);

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
