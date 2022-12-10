--
-- Update Monitors table to have a Janus_RTSP_User Column
--

SELECT 'Checking for `Janus_RTSP_User` in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'Janus_RTSP_User'
  ) > 0,
"SELECT 'Column Janus_RTSP_User already exists in Monitors'",
"ALTER TABLE Monitors ADD COLUMN `Janus_RTSP_User` INT(10) AFTER `Janus_Use_RTSP_Restream`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
