ALTER TABLE `Monitors` MODIFY `OutputCodec` enum('h264','mjpeg','mpeg1','mpeg2') default 'h264';
ALTER TABLE `Monitors` MODIFY `OutputContainer` enum('auto','mp4','mkv') default 'auto';

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Events'
     AND column_name = 'SaveJPEGs'
    ) > 0,
"SELECT 'Column SaveJPEGs already exists in Events'",
"ALTER TABLE `Events` ADD `SaveJPEGs` TINYINT AFTER `DefaultVideo`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
