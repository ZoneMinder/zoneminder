--
-- Update Servers table to have a PathPrefix field
--
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Servers'
	AND table_schema = DATABASE()
	AND column_name = 'PathPrefix'
	) > 0,
"SELECT 'Column PathPrefix already exists in Servers'",
"ALTER TABLE `Servers` ADD COLUMN `PathPrefix` TEXT AFTER Hostname"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
