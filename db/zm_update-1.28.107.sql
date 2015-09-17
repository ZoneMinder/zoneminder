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
	WHERE table_name = 'Servers'
	AND table_schema = DATABASE()
	AND column_name = 'Hostname'
	) > 0,
"SELECT 'Column Hostname already exists in Servers'",
"ALTER TABLE `Servers` ADD `Hostname` TEXT NOT NULL default '' AFTER `StateId`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
