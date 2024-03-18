--
-- Update Monitors table to have StartupDelay
--

SELECT 'Checking for StartupDelay in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'StartupDelay'
  ) > 0,
"SELECT 'Column StartupDelay already exists on Monitors'",
"ALTER TABLE Monitors ADD `StartupDelay` INT NOT NULL DEFAULT 0 AFTER `MQTT_Subscriptions`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
