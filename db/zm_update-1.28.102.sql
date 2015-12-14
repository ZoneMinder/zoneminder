--
-- Add Monitor Exif field
-- Used to enable or disable processing of the remote camera RTSP DESCRIBE response header
--
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'Exif'
	) > 0,
"SELECT 'Column Exif already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Exif` tinyint(1) unsigned NOT NULL default '0' AFTER `WebColour`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
