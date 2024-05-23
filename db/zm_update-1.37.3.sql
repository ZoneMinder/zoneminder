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
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF( 
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'MonitorPresets'
     AND column_name = 'ModelId'
    ) > 0,
"SELECT 'Column ModelId already exists in MonitorPresets'",
"ALTER TABLE `MonitorPresets` ADD `ModelId`  int(10) unsigned AFTER `Id`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE table_schema = DATABASE()
     AND table_name = 'MonitorPresets'
     AND column_name = 'ModelId'
    ) > 0,
"SELECT 'FOREIGN KEY for ModelId already exists in MonitorPresets'",
"ALTER TABLE `MonitorPresets` ADD FOREIGN KEY  (`ModelId`) REFERENCES `Models` (Id)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

UPDATE `MonitorPresets` SET `ModelId`=(SELECT `Id` FROM `Models` WHERE `Name`='IP8M-T2499EW') WHERE `Name` like 'Amcrest, IP8M-T2499EW
%';
