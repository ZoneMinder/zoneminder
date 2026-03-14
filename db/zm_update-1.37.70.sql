/* Change OutputCodec  from int to varchar(32) */
SELECT 'Checking for OutputCodecName in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'OutputCodecName'
  ) > 0,
"SELECT 'Column OutputCodecName already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `OutputCodecName` varchar(32) NOT NULL default '' AFTER `VideoWriter`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

UPDATE Monitors SET OutputCodecName='auto' where OutputCodec=0 AND OutputCodecName='';
UPDATE Monitors SET OutputCodecName='h264' where OutputCodec=27 AND OutputCodecName='';
UPDATE Monitors SET OutputCodecName='hevc' where OutputCodec=173 AND OutputCodecName='';
UPDATE Monitors SET OutputCodecName='vp9' where OutputCodec=167 AND OutputCodecName='';
UPDATE Monitors SET OutputCodecName='av1' where OutputCodec=225 or OutputCodec=226 AND OutputCodecName='';

