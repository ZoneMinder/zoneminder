--
-- This update adds ONVIF features
--

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'ONVIF_URL'
	) > 0,
"SELECT 'Column ONVIF_URL already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `ONVIF_URL` VARCHAR(255) NOT NULL DEFAULT '' AFTER `Triggers`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'ONVIF_Username'
	) > 0,
"SELECT 'Column ONVIF_Username already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `ONVIF_Username` VARCHAR(64) NOT NULL DEFAULT '' AFTER `ONVIF_URL`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'ONVIF_Password'
  ) > 0,
"SELECT 'Column ONVIF_Password already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `ONVIF_Password` VARCHAR(64) NOT NULL DEFAULT '' AFTER `ONVIF_Username`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'ONVIF_Options'
  ) > 0,
"SELECT 'Column ONVIF_Options already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `ONVIF_Options` VARCHAR(64) NOT NULL DEFAULT '' AFTER `ONVIF_Password`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

