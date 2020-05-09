
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Monitors'
      AND column_name = 'DefaultCodec'
    ) > 0,
    "SELECT 'Column DefaultCodec already exists in Monitors'",
    "ALTER TABLE Monitors ADD `DefaultCodec` enum('auto','MP4','MJPEG') NOT NULL default 'auto' AFTER `DefaultScale`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;
