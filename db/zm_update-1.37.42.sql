--
-- Update Monitors table to have RTSP2Web
--

SELECT 'Checking for RTSP2WebEnabled in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'RTSP2WebEnabled'
  ) > 0,
"SELECT 'Column RTSP2WebEnabled already exists on Monitors'",
 "ALTER TABLE `Monitors` ADD COLUMN `RTSP2WebEnabled` BOOLEAN NOT NULL default false AFTER `Decoding`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SELECT 'Checking for RTSP2WebType in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'RTSP2WebType'
  ) > 0,
"SELECT 'Column RTSP2WebType already exists on Monitors'",
 "ALTER TABLE `Monitors` ADD COLUMN `RTSP2WebType` enum('HLS','MSE','WebRTC') NOT NULL default 'WebRTC' AFTER `RTSP2WebEnabled`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
