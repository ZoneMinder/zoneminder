--
-- This updates a 1.28.107 database to 1.28.108
--

--
-- Add Monitor StateId field
--
SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Servers'
    AND table_schema = DATABASE()
    AND column_name = 'StateId'
    ) > 0,
"SELECT 'Column StateId already exists in Servers'",
"ALTER TABLE `Servers` ADD `StateId` int(10) unsigned AFTER `Name`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Servers'
	AND table_schema = DATABASE()
	AND column_name = 'Hostname'
	) > 0,
"SELECT 'Column Hostname already exists in Servers'",
"ALTER TABLE `Servers` ADD `Hostname` TEXT NOT NULL default '' AFTER `StateId`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
