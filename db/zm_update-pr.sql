--
-- Add PlateRecognizer.com stuff
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'AutoPlateRecognize'
    ) > 0,
"SELECT 'Column AutoPlateRecognize already exists in Filters'",
"ALTER TABLE Filters ADD `AutoPlateRecognize` tinyint(3) unsigned NOT NULL default '0' AFTER `AutoCopy`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Frames'
     AND column_name = 'Data_json'
    ) > 0,
"SELECT 'Column Data_json already exists in Frames'",
"ALTER TABLE `Frames` ADD `Data_json` text AFTER `Score`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
