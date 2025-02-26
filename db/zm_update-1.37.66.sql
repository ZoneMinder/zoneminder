SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'RTSP2WebStream'
    ) > 0,
"SELECT 'Column RTSP2WebStream already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `RTSP2WebStream` enum('Primary','Secondary') NOT NULL DEFAULT 'Primary' AFTER `RTSP2WebType`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
