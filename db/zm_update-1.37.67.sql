--
-- Update Monitors table to have Go2RTC
--

SELECT 'Checking for Go2RTCEnabled in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'Go2RTCEnabled'
  ) > 0,
"SELECT 'Column Go2RTCEnabled already exists on Monitors'",
 "ALTER TABLE `Monitors` ADD COLUMN `Go2RTCEnabled` BOOLEAN NOT NULL default false AFTER `Decoding`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SELECT 'Checking for Go2RTCType in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'Go2RTCType'
  ) > 0,
"SELECT 'Column Go2RTCType already exists on Monitors'",
 "ALTER TABLE `Monitors` ADD COLUMN `Go2RTCType` enum('HLS','MSE','WebRTC') NOT NULL default 'WebRTC' AFTER `Go2RTCEnabled`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
