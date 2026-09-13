--
-- Add Monitors.DeviceClass and the MonitorActions table.
--
-- A speaker is a monitor: it has an address, credentials, a stream and a
-- Controls entry, and everything ZoneMinder already knows how to do with a
-- monitor applies to it.  What it is not is a camera, and the console, the
-- montage and the action editor all need to tell the difference.  DeviceClass
-- carries that distinction without disturbing Type, which selects the capture
-- backend: an IP speaker still captures over Ffmpeg like any other RTSP
-- device.
--
-- MonitorActions records what a monitor does when it alarms.  The device
-- acted on is identified by TargetMonitorId and is frequently NOT the monitor
-- that triggered - the point of the feature is that a camera can sound a
-- speaker somewhere else.
--

SELECT 'Checking for DeviceClass in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'DeviceClass'
  ) > 0,
"SELECT 'Column DeviceClass already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `DeviceClass` enum('Camera','Speaker') NOT NULL default 'Camera' AFTER `Type`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Checking for the MonitorActions table';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLES
  WHERE table_name = 'MonitorActions'
  AND table_schema = DATABASE()
  ) > 0,
"SELECT 'Table MonitorActions already exists'",
"CREATE TABLE `MonitorActions` (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `MonitorId` int(10) unsigned NOT NULL,
  `TriggerOn` enum('EventStart','EventEnd','Alarm','Manual') NOT NULL default 'EventStart',
  `ActionType` enum('LightOn','LightOff','IndicatorLightOn','IndicatorLightOff','AudioPlay','AudioStop') NOT NULL default 'AudioPlay',
  `TargetMonitorId` int(10) unsigned NOT NULL,
  `AudioFile` int(10) unsigned default NULL,
  `Label` varchar(64) NOT NULL default '',
  `Enabled` tinyint(1) unsigned NOT NULL default '1',
  `Sequence` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY (`Id`),
  KEY `MonitorId_TriggerOn` (`MonitorId`,`TriggerOn`),
  KEY `TargetMonitorId` (`TargetMonitorId`)
)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
