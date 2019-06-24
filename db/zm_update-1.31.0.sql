--
-- This updates a 1.29.0 database to 1.30.0
--
SELECT 'Checking for SaveJPEGs in Monitors';
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

SELECT 'Checking for VideoWriter in Monitors';
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

SELECT 'Checking for EncoderParameters in Monitors';
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'EncoderParameters'
	) > 0,
"SELECT 'Column EncoderParameters exists in Monitors'",
"ALTER TABLE `Monitors` ADD `EncoderParameters` TEXT NOT NULL AFTER `VideoWriter`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SELECT 'Checking for DefaultVideo in Events';
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Events'
	AND table_schema = DATABASE()
	AND column_name = 'DefaultVideo'
	) > 0,
"SELECT 'Column DefaultVideo exists in Events'",
"ALTER TABLE `Events` ADD `DefaultVideo` VARCHAR( 64 ) NOT NULL default '' AFTER `AlarmFrames`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SELECT 'Checking for RecordAudio in Monitors';
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
-- The following alters various columns to allow NULLs
--

ALTER TABLE Monitors MODIFY Host varchar(64);
ALTER TABLE Monitors MODIFY LabelFormat varchar(64);
ALTER TABLE Monitors MODIFY LinkedMonitors varchar(255);
ALTER TABLE Monitors MODIFY Options varchar(255);
ALTER TABLE Monitors MODIFY Protocol varchar(16);
ALTER TABLE Monitors MODIFY User varchar(64);
ALTER TABLE Monitors MODIFY Pass varchar(64);
ALTER TABLE Monitors MODIFY RTSPDescribe tinyint(1) unsigned;
ALTER TABLE Monitors MODIFY ControlId int(10) unsigned;
ALTER TABLE Monitors MODIFY TrackDelay smallint(5) unsigned;
ALTER TABLE Monitors MODIFY ReturnDelay smallint(5) unsigned;
ALTER TABLE Monitors MODIFY EncoderParameters TEXT;
ALTER TABLE Monitors MODIFY Path varchar(255);
ALTER TABLE Monitors MODIFY V4LMultiBuffer tinyint(1) unsigned;

ALTER TABLE Users MODIFY MonitorIds tinytext;
ALTER TABLE Users MODIFY Language varchar(8);
ALTER TABLE Users MODIFY MaxBandwidth varchar(16);

