--
-- Update Monitors Table to include Janus_RTSP_Session_Timeout
--

SELECT 'Checking for Janus_RTSP_Session_Timeout in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'Janus_RTSP_Session_Timeout'
  ) > 0,
"SELECT 'Column Janus_RTSP_Session_Timeout already exists in Monitors'",
"ALTER TABLE Monitors ADD Janus_RTSP_Session_Timeout int(10) DEFAULT '0' AFTER `Janus_RTSP_User`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;