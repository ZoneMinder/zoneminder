--
-- This updates a 1.28.105 database to 1.28.106
--

--
-- Add Monitor RTSPDescribe field
-- Used to enable or disable processing of the remote camera RTSP DESCRIBE response header
--
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'RTSPDescribe'
	) > 0,
"SELECT 'Column RTSPDescribe already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `RTSPDescribe` tinyint(1) unsigned NOT NULL default '0' AFTER `Deinterlacing`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
