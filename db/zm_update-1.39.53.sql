--
-- This updates a 1.39.52 database to 1.39.53
--

--
-- Three shipped monitor presets pointed at the wrong control on installs
-- created before zm_create.sql.in looked controls up by name. Only rows that
-- still hold the old wrong control are changed.
--

UPDATE `MonitorPresets` SET `ControlId` = (SELECT MIN(`Id`) FROM `Controls` WHERE `Name` = 'Foscam FI9821W')
  WHERE `Name` = 'Foscam FI9821W FFMPEG H.264'
  AND `ControlId` = (SELECT MIN(`Id`) FROM `Controls` WHERE `Name` = 'Foscam FI8908W');

UPDATE `MonitorPresets` SET `ControlId` = (SELECT MIN(`Id`) FROM `Controls` WHERE `Name` = 'Loftek Sentinel')
  WHERE `Name` = 'Loftek Sentinel PTZ, 640x480, mjpeg'
  AND `ControlId` = (SELECT MIN(`Id`) FROM `Controls` WHERE `Name` = 'Foscam FI9821W');

UPDATE `MonitorPresets` SET `ControlId` = (SELECT MIN(`Id`) FROM `Controls` WHERE `Name` = 'SunEyes SP-P1802SWPTZ')
  WHERE `Name` = 'SunEyes SP-P1802SWPTZ'
  AND `ControlId` = (SELECT MIN(`Id`) FROM `Controls` WHERE `Name` = 'WanscamPT');
