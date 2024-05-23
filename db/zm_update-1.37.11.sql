--
-- Update Monitors table to have use_Amcrest_API
--

SELECT 'Checking for use_Amcrest_API in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'use_Amcrest_API'
  ) > 0,
"SELECT 'Column use_Amcrest_API already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `use_Amcrest_API` BOOLEAN NOT NULL default false AFTER `ONVIF_Event_Listener`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
