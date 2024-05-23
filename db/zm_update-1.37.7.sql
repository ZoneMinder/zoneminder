/* Change Cause from varchar(32) to TEXT.  We now include alarmed zone name */
ALTER TABLE `Events` MODIFY `Cause` TEXT;

--
-- Update Monitors table to have a ONVIF_Event_Listener Column
--

SELECT 'Checking for ONVIF_Event_Listener in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'ONVIF_Event_Listener'
  ) > 0,
"SELECT 'Column ONVIF_Event_Listener already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `ONVIF_Event_Listener` BOOLEAN NOT NULL default false AFTER `ONVIF_Options`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
