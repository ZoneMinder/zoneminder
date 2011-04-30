--
-- This updates a 1.24.2 database to the next version
--

--Add Colours colum. This is a harmless change for ZM 1.24.2 or ZM 1.24.3 without the patch, but is required to use the patch
ALTER TABLE `Monitors` ADD `Colours` TINYINT UNSIGNED NOT NULL DEFAULT '1' AFTER `Height`;

-- Replace now unused ZM_Y_IMAGE_DELTAS option with ZM_CPU_EXTENSIONS.
UPDATE `zm`.`Config` SET `Name` = 'ZM_CPU_EXTENSIONS',
`Prompt` = 'Use advanced CPU extensions to increase performance',
`Help` = 'When advanced processor extensions such as SSE2 or SSSE3 are available, ZoneMinder can use them, which should increase performance and reduce system load. Enabling this option on processors that do not support the advanced processors extensions used by ZoneMinder is harmless and will have no effect.' WHERE `Config`.`Id` = '27';

--
-- Add in remote ZoneMinder preset.
--
INSERT INTO `MonitorPresets` VALUES ('','Axis FFMPEG H.264','Ffmpeg',NULL,NULL,NULL,NULL,NULL,'rtsp://<host/address>/axis-media/media.amp?videocodec=h264',NULL,NULL,NULL,640,480,3,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO `MonitorPresets` VALUES ('','Vivotek FFMPEG','Ffmpeg',NULL,NULL,NULL,NULL,NULL,'rtsp://<host/address>:554/live.sdp',NULL,NULL,NULL,352,240,NULL,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO `MonitorPresets` VALUES ('','Axis FFMPEG','Ffmpeg',NULL,NULL,NULL,NULL,NULL,'rtsp://<host/address>/axis-media/media.amp',NULL,NULL,NULL,640,480,NULL,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO `MonitorPresets` VALUES ('','ACTi TCM FFMPEG','Ffmpeg',NULL,NULL,NULL,NULL,NULL,'rtsp://admin:123456@<host/address>:7070',NULL,NULL,NULL,320,240,NULL,NULL,0,NULL,NULL,NULL,100,100);
INSERT INTO `MonitorPresets` VALUES ('','Remote ZoneMinder','Remote',NULL,NULL,NULL,'http','simple','<ip-address>',80,'/cgi-bin/nph-zms?mode=jpeg&monitor=<monitor-id>&scale=100&maxfps=5&buffer=0',NULL,NULL,NULL,3,NULL,0,NULL,NULL,NULL,100,100);

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
