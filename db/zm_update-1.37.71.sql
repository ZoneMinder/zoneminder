SELECT 'Checking for DefaultPlayer in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'DefaultPlayer'
  ) > 0,
"SELECT 'Column DefaultPlayer already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `DefaultPlayer` varchar(64) AFTER `RTSP2WebType`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

UPDATE Monitors SET DefaultPlayer='rtsp2web_webrtc' WHERE RTSP2WebType='RTC' and DefaultPlayer IS NULL;
UPDATE Monitors SET DefaultPlayer='rtsp2web_mse' WHERE RTSP2WebType='MSE' and DefaultPlayer IS NULL;
UPDATE Monitors SET DefaultPlayer='rtsp2web_hls' WHERE RTSP2WebType='HLS' and DefaultPlayer IS NULL;
UPDATE Monitors SET DefaultPlayer='' WHERE DefaultPlayer IS NULL;

