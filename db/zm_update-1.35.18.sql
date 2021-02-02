SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'RTSPServer'
    ) > 0,
"SELECT 'Column RTSPServer already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `RTSPServer`  BOOLEAN NOT NULL DEFAULT FALSE AFTER `Longitude`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
