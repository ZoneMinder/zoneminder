SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'RTSPStreamName'
    ) > 0,
"SELECT 'Column RTSPStreamName already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `RTSPStreamName` varchar(255) NOT NULL default '' AFTER `RTSPServer`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
