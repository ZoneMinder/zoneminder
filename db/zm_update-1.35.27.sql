--
-- Add ModectDuringPTZ
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ModectDuringPTZ'
    ) > 0,
"SELECT 'Column ModectDuringPTZ already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `ModectDuringPTZ` tinyint(3) unsigned NOT NULL default '0' AFTER `ReturnDelay`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
