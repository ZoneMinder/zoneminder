--
-- Update Monitors table to have EventCloseMode
--

SELECT 'Checking for EventCloseMode in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'EventCloseMode'
  ) > 0,
"ALTER TABLE Monitors MODIFY `EventCloseMode`  enum('system', 'time', 'duration', 'idle', 'alarm') NOT NULL DEFAULT 'system'",
"ALTER TABLE Monitors ADD `EventCloseMode`  enum('system', 'time', 'duration', 'idle', 'alarm') NOT NULL DEFAULT 'system' AFTER SectionLengthWarn"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
