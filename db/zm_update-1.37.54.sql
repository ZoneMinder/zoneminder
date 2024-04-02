--
-- Update Monitors table to have ONVIF_Events_Path
--

SELECT 'Checking for ONVIF_Events_Path in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'ONVIF_Events_Path'
  ) > 0,
"SELECT 'Column ONVIF_Events_Path already exists on Monitors'",
"ALTER TABLE Monitors ADD `ONVIF_Events_Path` varchar(20) DEFAULT '/Events' NOT NULL AFTER ONVIF_URL" 
));

PREPARE stmt FROM @s;
EXECUTE stmt;


