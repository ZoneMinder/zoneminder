--
-- This updates a 1.31.42 database to 1.31.43
--
-- Add WebSite enum to Monitor.Type
-- Add Refresh column to Monitors table
--

ALTER TABLE `Monitors` 
CHANGE COLUMN `Type` `Type` ENUM('Local', 'Remote', 'File', 'Ffmpeg', 'Libvlc', 'cURL', 'WebSite') NOT NULL DEFAULT 'Local' ;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'Refresh'
    ) > 0,
"SELECT 'Column Refresh exists in Monitors'",
"ALTER TABLE Monitors ADD `Refresh` int(10) unsigned default NULL AFTER `ZoneCount`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

