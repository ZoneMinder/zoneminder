--
-- This updates a 1.30.3 database to 1.31.0
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
"ALTER TABLE `Filters` DROP PRIMARY KEY, ADD `Id` int(10) unsigned NOT NULL auto_increment PRIMARY KEY FIRST, ADD KEY `Name` (`Name`);"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- The following alters various columns to allow NULLs
--

ALTER TABLE Monitors MODIFY LabelFormat varchar(64);
ALTER TABLE Monitors MODIFY Host varchar(64);
ALTER TABLE Monitors MODIFY Protocol varchar(16);
ALTER TABLE Monitors MODIFY Options varchar(255);
ALTER TABLE Monitors MODIFY LinkedMonitors varchar(255);
ALTER TABLE Monitors MODIFY User varchar(64);
ALTER TABLE Monitors MODIFY Pass varchar(64);
ALTER TABLE Monitors MODIFY RTSPDescribe tinyint(1) unsigned;
ALTER TABLE Monitors MODIFY ControlId int(10) unsigned;
ALTER TABLE Monitors MODIFY TrackDelay smallint(5) unsigned;
ALTER TABLE Monitors MODIFY ReturnDelay smallint(5) unsigned;

ALTER TABLE Users MODIFY MonitorIds tinytext;
ALTER TABLE Users MODIFY Language varchar(8);
ALTER TABLE Users MODIFY MaxBandwidth varchar(16);


--
-- Add table for Storagea Areas
--

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

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'StorageId'
    ) > 0,
"SELECT 'Column StorageId exists in Monitors'",
"ALTER TABLE Monitors ADD `StorageId` smallint(5) unsigned AFTER `ServerId`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add StorageId column to Eventss
--

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

-- Increase the size of the Pid field for FreeBSD
ALTER TABLE Logs MODIFY Pid int(10);

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'SaveJPEGs'
	) > 0,
"SELECT 'Column SaveJPEGs exists in Monitors'",
"ALTER TABLE `Monitors` ADD `SaveJPEGs` TINYINT NOT NULL DEFAULT '3' AFTER `Deinterlacing`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- h264 videostorage changes
--

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'VideoWriter'
	) > 0,
"SELECT 'Column VideoWriter exists in Monitors'",
"ALTER TABLE `Monitors` ADD `VideoWriter` TINYINT NOT NULL DEFAULT '0' AFTER `SaveJPEGs`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'EncoderParameters'
	) > 0,
"SELECT 'Column EncoderParameters exists in Monitors'",
"ALTER TABLE `Monitors` ADD `EncoderParameters` TEXT AFTER `VideoWriter`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Events'
	AND table_schema = DATABASE()
	AND column_name = 'DefaultVideo'
	) > 0,
"SELECT 'Column DefaultVideo exists in Events'",
"ALTER TABLE `Events` ADD `DefaultVideo` VARCHAR( 64 ) NOT NULL DEFAULT '' AFTER `AlarmFrames`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'RecordAudio'
	) > 0,
"SELECT 'Column RecordAudio exists in Monitors'",
"ALTER TABLE `Monitors` ADD `RecordAudio` TINYINT NOT NULL DEFAULT '0' AFTER `EncoderParameters`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Changing StorageId to be NOT NULL and default 0
--

UPDATE Monitors SET StorageId = 0 WHERE StorageId IS NULL;
ALTER TABLE Monitors MODIFY `StorageId`	smallint(5) unsigned NOT NULL default 0;
UPDATE Events SET StorageId = 0 WHERE StorageId IS NULL;
ALTER TABLE Events MODIFY `StorageId`	smallint(5) unsigned NOT NULL default 0;


--
-- Add an Orientation column to Events so that we can store the orientation in the event instead of just in the monitor.
--

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

--
-- Alter type of Messages column from VARCHAR(255) to TEXT
--

-- ALTER TABLE Logs ALTER  Message DROP DEFAULT;
ALTER TABLE Logs MODIFY Message TEXT NOT NULL;

ALTER TABLE Config MODIFY DefaultValue TEXT;

--
-- Add StateId Column to Events.
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Events'
    AND table_schema = DATABASE()
    AND column_name = 'StateId'
    ) > 0,
"SELECT 'Column StateId exists in Events'",
"ALTER TABLE Events ADD `StateId` int(10) unsigned default NULL AFTER `Notes`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
--
-- Add StateId Column to Events.
--

ALTER TABLE Monitors MODIFY EncoderParameters TEXT;
ALTER TABLE Monitors MODIFY Path VARCHAR(255);
