--
-- This updates a 1.39.48 database to 1.39.49
--

--
-- The Ffmpeg presets for Axis, Vivotek and ACTi had their rtsp url in Host and no Path.
--

UPDATE `MonitorPresets` SET `Path` = `Host`, `Host` = NULL WHERE `Type` = 'Ffmpeg' AND `Path` IS NULL AND `Host` LIKE 'rtsp://%';
