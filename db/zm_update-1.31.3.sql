--
-- This adds StorageAreas
--

SELECT 'Checking For Storage Table';
SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE table_name = 'Storage'
    AND table_schema = DATABASE()
    ) > 0,
"SELECT 'Storage table exists'",
"CREATE TABLE `Storage` (
    `Id`    smallint(5) unsigned NOT NULL auto_increment,
    `Path`  varchar(64) NOT NULL default '',
    `Name`  varchar(64) NOT NULL default '',
    PRIMARY KEY (`Id`)
)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add StorageId column to Monitors
--

SELECT 'Checking For StorageId in Monitors';
SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'StorageId'
    ) > 0,
"SELECT 'Column StorageId exists in Monitors'",
"ALTER TABLE Monitors ADD `StorageId` smallint(5) unsigned NOT NULL default 0 AFTER `ServerId`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add StorageId column to Eventss
--

SELECT 'Checking For StorageId in Events';
SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Events'
    AND table_schema = DATABASE()
    AND column_name = 'StorageId'
    ) > 0,
"SELECT 'Column StorageId exists in Events'",
"ALTER TABLE Events ADD `StorageId` smallint(5) unsigned NOT NULL default 0 AFTER `MonitorId`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SELECT 'Updating Monitors SETTING StorageId to default';
UPDATE Monitors SET StorageId = 0 WHERE StorageId IS NULL;
ALTER TABLE Monitors MODIFY `StorageId`	smallint(5) unsigned NOT NULL default 0;
UPDATE Events SET StorageId = 0 WHERE StorageId IS NULL;
ALTER TABLE Events MODIFY `StorageId`	smallint(5) unsigned NOT NULL default 0;

SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Events'
  AND table_schema = DATABASE()
  AND column_name = 'Orientation'
  ) > 0,
"SELECT 'Column Orientation exists in Events'",
"ALTER TABLE `Events` ADD `Orientation`  enum('0','90','180','270','hori','vert') NOT NULL default '0' AFTER `Notes`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Update Monitors table to have an Index on ServerId
--
SELECT 'Create Index For ServerId on Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND index_name = 'Monitors_ServerId_idx'
  ) > 0,
"SELECT 'Monitors_ServerId Index already exists on Monitors table'",
"CREATE INDEX `Monitors_ServerId_idx` ON `Monitors` (`ServerId`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;


--
-- Update Server table to have an Index on Name
--
SELECT 'Create Index FOR Name on Servers';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE table_name = 'Servers'
  AND table_schema = DATABASE()
  AND index_name = 'Servers_Name_idx'
  ) > 0,
"SELECT 'Servers_Name Index already exists on Servers table'",
"CREATE INDEX `Servers_Name_idx` ON `Servers` (`Name`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;


SELECT 'ALTER TABLE Logs MODIFY Message TEXT NOT NULL';
-- ALTER TABLE Logs ALTER  Message DROP DEFAULT;
ALTER TABLE Logs MODIFY Message TEXT NOT NULL;

SELECT 'ALTER TABLE Config MODIFY DefaultValue TEXT';
ALTER TABLE Config MODIFY DefaultValue TEXT;


-- 
-- Add an Id column and make it the primary key of the Filters table
--

SELECT 'Check for Id column in Filter';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Filters'
  AND table_schema = DATABASE()
  AND column_name = 'Id'
  ) > 0,
"SELECT 'Column Id exists in Filters'",
"ALTER TABLE `Filters` DROP PRIMARY KEY, ADD `Id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY FIRST, ADD KEY `Name` (`Name`);"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

