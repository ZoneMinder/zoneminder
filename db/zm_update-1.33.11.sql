
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Monitors'
      AND column_name = 'DecoderHWAccelName'
    ) > 0,
    "SELECT 'Column DecoderHWAccelName already exists in Monitors'",
    "ALTER TABLE Monitors ADD `DecoderHWAccelName`  varchar(64) AFTER `Deinterlacing`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Monitors'
      AND column_name = 'DecoderHWAccelDevice'
    ) > 0,
    "SELECT 'Column DecoderHWAccelDevice already exists in Monitors'",
    "ALTER TABLE Monitors ADD `DecoderHWAccelDevice` varchar(255) AFTER `DecoderHWAccelName`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;
