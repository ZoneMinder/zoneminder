--
-- Update Monitors table to have EventStartMode and EventCloseMode
--

/*
SELECT 'Checking for EventStartMode in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'EventStartMode'
  ) > 0,
"SELECT 'Column EventStartMode already exists on Monitors'",
"ALTER TABLE Monitors add EventStartMode enum('immediate','time') NOT NULL DEFAULT 'immediate' AFTER SectionLengthWarn"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
*/

SELECT 'Checking for EventCloseMode in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'EventCloseMode'
  ) > 0,
"ALTER TABLE Monitors MODIFY `EventCloseMode`  enum('system', 'time', 'duration', 'idle', 'alarm') NOT NULL DEFAULT 'system'",
"ALTER TABLE Monitors add `EventCloseMode`  enum('system', 'time', 'duration', 'idle', 'alarm') NOT NULL DEFAULT 'system' AFTER SectionLengthWarn"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
