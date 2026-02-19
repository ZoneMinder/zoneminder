--
-- Rename RTSP2WebStream to StreamChannel and update enum values
-- This applies to Go2RTC, Janus, and RTSP2Web streaming
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'StreamChannel'
    ) > 0,
"SELECT 'Column StreamChannel already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `StreamChannel` enum('Restream','CameraDirectPrimary','CameraDirectSecondary') NOT NULL DEFAULT 'Restream' AFTER `RTSP2WebType`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Migrate data from RTSP2WebStream to StreamChannel if RTSP2WebStream exists
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'RTSP2WebStream'
    ) > 0,
"UPDATE `Monitors` SET `StreamChannel` = CASE
    WHEN `RTSP2WebStream` = 'Secondary' THEN 'CameraDirectSecondary'
    ELSE 'Restream'
END",
"SELECT 'Column RTSP2WebStream does not exist, skipping migration'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Drop old RTSP2WebStream column if it exists
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'RTSP2WebStream'
    ) > 0,
"ALTER TABLE `Monitors` DROP COLUMN `RTSP2WebStream`",
"SELECT 'Column RTSP2WebStream does not exist, nothing to drop'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
