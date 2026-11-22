--
-- This updates a 1.39.54 database to 1.39.55
--

--
-- Add ONVIF_Event_Tags to Monitors: when set, events raised by the ONVIF event
-- listener are tagged with the objects the camera reports (person, vehicle,
-- package, ...), or with the alarm's topic when it names none.
--

SELECT 'Checking for ONVIF_Event_Tags in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'ONVIF_Event_Tags'
  ) > 0,
"SELECT 'Column ONVIF_Event_Tags already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `ONVIF_Event_Tags` BOOLEAN NOT NULL DEFAULT FALSE AFTER `ONVIF_Event_Listener`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
