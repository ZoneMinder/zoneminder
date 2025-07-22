--
-- Update Monitors table to have ONVIF_Alarm_Text
--

SELECT 'Checking for ONVIF_Alarm_Text in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'ONVIF_Alarm_Text'
  ) > 0,
"SELECT 'Column ONVIF_Alarm_Text already exists in Monitors'",
"ALTER TABLE Monitors ADD ONVIF_Alarm_Text varchar(30) DEFAULT 'MotionAlarm' AFTER `ONVIF_Event_Listener`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
