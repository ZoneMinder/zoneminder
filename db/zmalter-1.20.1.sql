--
-- This updates a 1.20.0 database to 1.20.1
--
-- Make changes to Monitors table
--
alter table Monitors add column Controllable tinyint(3) unsigned NOT NULL default '0';
alter table Monitors add column ControlId int(10) unsigned NOT NULL default '0';
alter table Monitors add column ControlDevice varchar(255) default NULL;
alter table Monitors add column ControlAddress varchar(255) default NULL;
alter table Monitors add column TrackMotion tinyint(3) unsigned NOT NULL default '0';
alter table Monitors add column TrackDelay smallint(5) unsigned NOT NULL default '0';
alter table Monitors add column ReturnLocation tinyint(3) NOT NULL default '-1';
alter table Monitors add column ReturnDelay smallint(5) unsigned NOT NULL default '0';

--
-- Add new table `Controls`
--
CREATE TABLE Controls (
  Id int(10) unsigned NOT NULL auto_increment,
  Name varchar(64) NOT NULL default '',
  Type enum('Local','Remote') NOT NULL default 'Local',
  Command varchar(255) default NULL,

  CanWake tinyint(3) unsigned NOT NULL default '0',
  CanSleep tinyint(3) unsigned NOT NULL default '0',
  CanReset tinyint(3) unsigned NOT NULL default '0',

  CanZoom tinyint(3) unsigned NOT NULL default '0',
  CanAutoZoom tinyint(3) unsigned NOT NULL default '0',
  CanZoomAbs tinyint(3) unsigned NOT NULL default '0',
  CanZoomRel tinyint(3) unsigned NOT NULL default '0',
  CanZoomCon tinyint(3) unsigned NOT NULL default '0',
  MinZoomRange int(10) unsigned default NULL,
  MaxZoomRange int(10) unsigned default NULL,
  MinZoomStep int(10) unsigned default NULL,
  MaxZoomStep int(10) unsigned default NULL,
  HasZoomSpeed tinyint(3) unsigned NOT NULL default '0',
  MinZoomSpeed int(10) unsigned default NULL,
  MaxZoomSpeed int(10) unsigned default NULL,

  CanFocus tinyint(3) unsigned NOT NULL default '0',
  CanAutoFocus tinyint(3) unsigned NOT NULL default '0',
  CanFocusAbs tinyint(3) unsigned NOT NULL default '0',
  CanFocusRel tinyint(3) unsigned NOT NULL default '0',
  CanFocusCon tinyint(3) unsigned NOT NULL default '0',
  MinFocusRange int(10) unsigned default NULL,
  MaxFocusRange int(10) unsigned default NULL,
  MinFocusStep int(10) unsigned default NULL,
  MaxFocusStep int(10) unsigned default NULL,
  HasFocusSpeed tinyint(3) unsigned NOT NULL default '0',
  MinFocusSpeed int(10) unsigned default NULL,
  MaxFocusSpeed int(10) unsigned default NULL,

  CanIris tinyint(3) unsigned NOT NULL default '0',
  CanAutoIris tinyint(3) unsigned NOT NULL default '0',
  CanIrisAbs tinyint(3) unsigned NOT NULL default '0',
  CanIrisRel tinyint(3) unsigned NOT NULL default '0',
  CanIrisCon tinyint(3) unsigned NOT NULL default '0',
  MinIrisRange int(10) unsigned default NULL,
  MaxIrisRange int(10) unsigned default NULL,
  MinIrisStep int(10) unsigned default NULL,
  MaxIrisStep int(10) unsigned default NULL,
  HasIrisSpeed tinyint(3) unsigned NOT NULL default '0',
  MinIrisSpeed int(10) unsigned default NULL,
  MaxIrisSpeed int(10) unsigned default NULL,

  CanGain tinyint(3) unsigned NOT NULL default '0',
  CanAutoGain tinyint(3) unsigned NOT NULL default '0',
  CanGainAbs tinyint(3) unsigned NOT NULL default '0',
  CanGainRel tinyint(3) unsigned NOT NULL default '0',
  CanGainCon tinyint(3) unsigned NOT NULL default '0',
  MinGainRange int(10) unsigned default NULL,
  MaxGainRange int(10) unsigned default NULL,
  MinGainStep int(10) unsigned default NULL,
  MaxGainStep int(10) unsigned default NULL,
  HasGainSpeed tinyint(3) unsigned NOT NULL default '0',
  MinGainSpeed int(10) unsigned default NULL,
  MaxGainSpeed int(10) unsigned default NULL,

  CanWhite tinyint(3) unsigned NOT NULL default '0',
  CanAutoWhite tinyint(3) unsigned NOT NULL default '0',
  CanWhiteAbs tinyint(3) unsigned NOT NULL default '0',
  CanWhiteRel tinyint(3) unsigned NOT NULL default '0',
  CanWhiteCon tinyint(3) unsigned NOT NULL default '0',
  MinWhiteRange int(10) unsigned default NULL,
  MaxWhiteRange int(10) unsigned default NULL,
  MinWhiteStep int(10) unsigned default NULL,
  MaxWhiteStep int(10) unsigned default NULL,
  HasWhiteSpeed tinyint(3) unsigned NOT NULL default '0',
  MinWhiteSpeed int(10) unsigned default NULL,
  MaxWhiteSpeed int(10) unsigned default NULL,

  HasPresets tinyint(3) unsigned NOT NULL default '0',
  NumPresets tinyint(3) unsigned NOT NULL default '0',
  HasHomePreset tinyint(3) unsigned NOT NULL default '0',
  CanSetPresets tinyint(3) unsigned NOT NULL default '0',

  CanMove tinyint(3) unsigned NOT NULL default '0',
  CanMoveDiag tinyint(3) unsigned NOT NULL default '0',
  CanMoveMap tinyint(3) unsigned NOT NULL default '0',
  CanMoveAbs tinyint(3) unsigned NOT NULL default '0',
  CanMoveRel tinyint(3) unsigned NOT NULL default '0',
  CanMoveCon tinyint(3) unsigned NOT NULL default '0',
  CanPan tinyint(3) unsigned NOT NULL default '0',
  MinPanRange int(10) default NULL,
  MaxPanRange int(10) default NULL,
  MinPanStep int(10) default NULL,
  MaxPanStep int(10) default NULL,
  HasPanSpeed tinyint(3) unsigned NOT NULL default '0',
  MinPanSpeed int(10) default NULL,
  MaxPanSpeed int(10) default NULL,
  HasTurboPan tinyint(3) unsigned NOT NULL default '0',
  TurboPanSpeed int(10) default NULL,
  CanTilt tinyint(3) unsigned NOT NULL default '0',
  MinTiltRange int(10) default NULL,
  MaxTiltRange int(10) default NULL,
  MinTiltStep int(10) default NULL,
  MaxTiltStep int(10) default NULL,
  HasTiltSpeed tinyint(3) unsigned NOT NULL default '0',
  MinTiltSpeed int(10) default NULL,
  MaxTiltSpeed int(10) default NULL,
  HasTurboTilt tinyint(3) unsigned NOT NULL default '0',
  TurboTiltSpeed int(10) default NULL,

  CanAutoScan tinyint(3) unsigned NOT NULL default '0',
  NumScanPaths tinyint(3) unsigned NOT NULL default '0',

  PRIMARY KEY  (Id),
  UNIQUE KEY UC_Id (Id)
) TYPE=MyISAM;

--
-- Some sample control protocol definitions
--
insert into Controls values (1,'pelco-d','Local','/usr/local/bin/zmcontrol-pelco-d.pl',1,1,0,1,1,0,0,1,NULL,NULL,NULL,NULL,1,0,3,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,20,1,1,1,1,0,0,0,1,1,NULL,NULL,NULL,NULL,1,0,63,1,254,1,NULL,NULL,NULL,NULL,1,0,63,1,254,0,0);
insert into Controls values (2,'visca','Local','/usr/local/bin/zmcontrol-visca.pl',1,1,0,1,0,0,0,1,0,16384,10,4000,1,1,6,1,1,1,0,1,0,1536,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,3,1,1,1,1,0,1,1,0,1,-15578,15578,100,10000,1,1,50,1,254,1,-7789,7789,100,5000,1,1,50,1,254,0,0);
insert into Controls values (3,'KX-HCM10','Remote','/usr/local/bin/zmcontrol-kx-hcm10.pl',0,0,0,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,8,1,1,1,0,1,0,0,1,1,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,1,NULL,NULL,NULL,NULL,0,NULL,NULL,0,NULL,0,0);
insert into Controls values (4,'pelco-d-full','Local','/usr/local/bin/zmcontrol-pelco-d.pl',1,1,0,1,1,0,0,1,NULL,NULL,NULL,NULL,1,0,3,1,1,0,0,1,NULL,NULL,NULL,NULL,0,NULL,NULL,1,1,0,1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,1,0,1,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,20,1,1,1,1,0,0,0,1,1,NULL,NULL,NULL,NULL,1,0,63,1,254,1,NULL,NULL,NULL,NULL,1,0,63,1,254,0,0);

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
