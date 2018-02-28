ALTER TABLE `Monitors` MODIFY `OutputCodec`     int(10) UNSIGNED NOT NULL default 0;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'Encoder'
    ) > 0,
"SELECT 'Column Encoder already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Encoder`     enum('auto','h264','h264_omx','mjpeg','mpeg1','mpeg2')  AFTER `OutputCodec`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

