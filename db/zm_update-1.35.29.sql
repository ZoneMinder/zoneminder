<<<<<<< HEAD
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ManufacturerId'
    ) > 0,
"SELECT 'Column ManufacturerId already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `ManufacturerId`  int(10) unsigned AFTER `StorageId`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ManufacturerId'
    ) > 0,
"SELECT 'FOREIGN KEY for ManufacturerId already exists in Monitors'",
"ALTER TABLE `Monitors` ADD FOREIGN KEY  (`ManufacturerId`) REFERENCES `Manufacturers` (Id)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF( 
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ModelId'
    ) > 0,
"SELECT 'Column ModelId already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `ModelId`  int(10) unsigned AFTER `ManufacturerId`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ModelId'
    ) > 0,
"SELECT 'FOREIGN KEY for ModelId already exists in Monitors'",
"ALTER TABLE `Monitors` ADD FOREIGN KEY  (`ModelId`) REFERENCES `Models` (Id)"
=======
--
-- Add AutoUnarchive action to Filters
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'AutoUnarchive'
    ) > 0,
"SELECT 'Column AutoUunarchive already exists in Filters'",
"ALTER TABLE Filters ADD `AutoUnarchive` tinyint(3) unsigned NOT NULL default '0' AFTER `AutoArchive`"
>>>>>>> master
));

PREPARE stmt FROM @s;
EXECUTE stmt;
