--
-- Add UpdateDiskSpace action to Filters
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'AutoMove'
    ) > 0,
"SELECT 'Column AutoMove already exists in Filters'",
"ALTER TABLE Filters ADD `AutoMove` tinyint(3) unsigned NOT NULL default '0' AFTER `AutoDelete`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'AutoMoveTo'
    ) > 0,
"SELECT 'Column AutoMoveTo already exists in Filters'",
"ALTER TABLE Filters ADD `AutoMoveTo` smallint(5) unsigned NOT NULL default '0' AFTER `AutoMove`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

