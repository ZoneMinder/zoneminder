--
-- This updates a 1.28.0 database to 1.28.99.0
--


--
-- Add Config* fields; used for specifying ONVIF/PSIA options
--
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'ConfigType'
	) > 0,
"SELECT 'Column ConfigType already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `ConfigType` ENUM('None','ONVIF','PSIA') NOT NULL DEFAULT 'None' AFTER `Triggers`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add Config* fields; used for specifying ONVIF/PSIA options
--
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'ConfigURL'
	) > 0,
"SELECT 'Column ConfigURL already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `ConfigURL` VARCHAR(255) NOT NULL DEFAULT '' AFTER `ConfigType`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add Config* fields; used for specifying ONVIF/PSIA options
--
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'ConfigOptions'
	) > 0,
"SELECT 'Column ConfigOptions already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `ConfigOptions` VARCHAR(64) NOT NULL DEFAULT '' AFTER `ConfigURL`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
