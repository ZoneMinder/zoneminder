--
-- This updates a 1.29.0 database to 1.29.1
--
--

-- 
-- Add an Id column and make it the primary key of the Filters table
--
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Filters'
  AND table_schema = DATABASE()
  AND column_name = 'Id'
  ) > 0,
"SELECT 'Column Id exists in Filters'",
"ALTER TABLE `Filters` DROP PRIMARY KEY; ALTER TABLE `Filters` ADD `Id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY FIRST; ALTER TABLE Filters ADD KEY `Name` (`Name`);"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

