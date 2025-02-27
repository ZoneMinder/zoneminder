
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Monitors'
      AND column_name = 'EncoderHWAccelName'
    ) > 0,
    "SELECT 'Column EncoderHWAccelName already exists in Monitors'",
    "ALTER TABLE Monitors ADD `EncoderHWAccelName`  varchar(64) AFTER `Encoder`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Monitors'
      AND column_name = 'EncoderHWAccelDevice'
    ) > 0,
    "SELECT 'Column EncoderHWAccelDevice already exists in Monitors'",
    "ALTER TABLE Monitors ADD `EncoderHWAccelDevice` varchar(255) AFTER `EncoderHWAccelName`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ObjectDetection'
    ) > 0,
"SELECT 'Column ObjectDetection already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `ObjectDetection` enum('none','quadra', 'speedai') NOT NULL DEFAULT 'none' AFTER `AnalysisImage`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;



SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ObjectDetectionModel'
    ) > 0,
"SELECT 'Column ObjectDetectionModel already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `ObjectDetectionModel` VARCHAR(255) NOT NULL DEFAULT '' AFTER `ObjectDetection`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ObjectDetectionObjectThreshold'
    ) > 0,
"SELECT 'Column ObjectDetectionObjectThreshold already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `ObjectDetectionObjectThreshold` FLOAT NOT NULL default 0.4 AFTER `ObjectDetectionModel`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ObjectDetectionNMSThreshold'
    ) > 0,
"SELECT 'Column ObjectDetectionNMSThreshold already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `ObjectDetectionNMSThreshold` FLOAT NOT NULL default 0.25 AFTER `ObjectDetectionObjectThreshold`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

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
