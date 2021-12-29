--
-- Update Monitors table to have a MQTT_Enabled Column
--

SELECT 'Checking for MQTT_Enabled in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'MQTT_Enabled'
  ) > 0,
"SELECT 'Column MQTT_Enabled already exists in Monitors'",
"ALTER TABLE Monitors ADD COLUMN `MQTT_Enabled` BOOLEAN NOT NULL DEFAULT false AFTER `Importance`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Update Monitors table to have a MQTT_Subscriptions Column
--

SELECT 'Checking for MQTT_Subscriptions in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'MQTT_Subscriptions'
  ) > 0,
"SELECT 'Column MQTT_Subscriptions already exists in Monitors'",
"ALTER TABLE Monitors ADD COLUMN `MQTT_Subscriptions` varchar(255) NOT NULL default '' AFTER `MQTT_Enabled`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
