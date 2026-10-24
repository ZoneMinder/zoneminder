--
-- This updates a 1.39.42 database to 1.39.43
--

--
-- Add Go2RTCDisableBackChannel to Monitors.
--
-- Some cameras, particularly Dahua cameras, may fail to record events or
-- handle ONVIF correctly when live viewing is active through go2rtc if
-- backchannel support is enabled.  The #backchannel=0 parameter disables
-- backchannel support for the RTSP source in go2rtc.  This column controls
-- whether the parameter is automatically added to the RTSP stream paths.
-- It defaults to 1 to preserve the existing behavior.  Setting it to 0
-- leaves the RTSP stream paths unchanged.
--

SELECT 'Checking for Go2RTCDisableBackChannel in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'Go2RTCDisableBackChannel'
  ) > 0,
"SELECT 'Column Go2RTCDisableBackChannel already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `Go2RTCDisableBackChannel` BOOLEAN NOT NULL DEFAULT TRUE"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
