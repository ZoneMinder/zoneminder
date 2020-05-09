--
-- Add CopyTo action to Filters
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'AutoCopy'
    ) > 0,
"SELECT 'Column AutoCopy already exists in Filters'",
"ALTER TABLE Filters ADD `AutoCopy` tinyint(3) unsigned NOT NULL default '0' AFTER `AutoMove`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'AutoCopyTo'
    ) > 0,
"SELECT 'Column AutoCopyTo already exists in Filters'",
"ALTER TABLE Filters ADD `AutoCopyTo` smallint(5) unsigned NOT NULL default '0' AFTER `AutoCopy`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'Query_json'
    ) > 0,
"SELECT 'Column Query_json already exists in Filters'",
"ALTER TABLE `Filters` Change `Query` `Query_json` text NOT NULL"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Events'
     AND column_name = 'SecondaryStorageId'
    ) > 0,
"SELECT 'Column SecondaryStorageId already exists in Events'",
"ALTER TABLE `Events` ADD `SecondaryStorageId`  smallint(5) unsigned default 0 AFTER `StorageId`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
