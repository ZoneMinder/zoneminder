
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
