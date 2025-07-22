--
-- Update Monitors table to have WallClockTimestamps
--

SELECT 'Checking for WallClockTImestamps in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'WallClockTimestamps'
  ) > 0,
"SELECT 'Column WallClockTimestamps already exists on Monitors'",
"ALTER TABLE Monitors ADD `WallClockTimestamps` TINYINT NOT NULL DEFAULT '0' AFTER `EncoderParameters`" 
));

PREPARE stmt FROM @s;
EXECUTE stmt;


