--
-- This update adds EventStartCommand and EventEndCommand
--

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'EventEndCommand'
	) > 0,
"SELECT 'Column EventEndCommand already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `EventEndCommand` VARCHAR(255) NOT NULL DEFAULT '' AFTER `Triggers`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'EventStartCommand'
	) > 0,
"SELECT 'Column EventStartCommand already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `EventStartCommand` VARCHAR(255) NOT NULL DEFAULT '' AFTER `Triggers`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
