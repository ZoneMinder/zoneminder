--
-- Add audio detection settings to Monitors.
--
-- ZoneMinder has always carried audio without listening to it: the packets go
-- into the event and nothing reads them.  These three columns let a monitor
-- score on how loud it is, alongside motion.
--
-- AudioThreshold and the level it is compared against are both on a 0-100
-- dBFS-derived scale rather than a raw amplitude ratio.  Linear amplitude is
-- unusable as a setting -- ordinary speech sits at 1-3% of full scale, so every
-- sound worth catching would be crammed into the bottom two points of the range.
--
-- A threshold of 0 means detection is off for that monitor, which is why it is
-- also the default: enabling AudioDetection without choosing a threshold must
-- not alarm on silence.
--
-- AudioAlarmScore defaults to 9 to match what an ONVIF or Amcrest alarm already
-- contributes, so an audio alarm is worth about the same as a camera-reported
-- one until the operator decides otherwise.
--

SELECT 'Checking for AudioDetection in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'AudioDetection'
  ) > 0,
"SELECT 'Column AudioDetection already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `AudioDetection` tinyint(1) unsigned NOT NULL default '0' AFTER `RecordAudio`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Checking for AudioThreshold in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'AudioThreshold'
  ) > 0,
"SELECT 'Column AudioThreshold already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `AudioThreshold` tinyint(3) unsigned NOT NULL default '0' AFTER `AudioDetection`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Checking for AudioAlarmScore in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'AudioAlarmScore'
  ) > 0,
"SELECT 'Column AudioAlarmScore already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `AudioAlarmScore` smallint(5) unsigned NOT NULL default '9' AFTER `AudioThreshold`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
