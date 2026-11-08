--
-- Add audio playback capabilities to Controls, and an entry for ONVIF IP
-- speakers.
--
-- An IP speaker is controllable but has none of the capabilities the Controls
-- table already models: nothing moves, focuses or lights up.  What it does is
-- play a sound file it already holds, selected by a numeric id, and carry an
-- output volume.  CanAudioPlay drives the play/stop buttons and the sound
-- selector; MinAudioFile/MaxAudioFile bound that selector; CanAudioVolume
-- drives the volume pair.
--
-- Measured against a device reporting ONVIF Manufacturer "IPSpeaker" and
-- firmware CS20-V3.3.45N.  Its file ids fall in two windows, 10-14 for the
-- built-in sounds and 20-30 for operator uploads; the row below covers the
-- built-in window because a fresh device has nothing uploaded and the
-- firmware refuses an empty slot.
--

SELECT 'Checking for CanAudioPlay in Controls';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Controls'
  AND table_schema = DATABASE()
  AND column_name = 'CanAudioPlay'
  ) > 0,
"SELECT 'Column CanAudioPlay already exists in Controls'",
"ALTER TABLE `Controls` ADD COLUMN `CanAudioPlay` tinyint(3) unsigned NOT NULL default '0' AFTER `NumScanPaths`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Checking for MinAudioFile in Controls';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Controls'
  AND table_schema = DATABASE()
  AND column_name = 'MinAudioFile'
  ) > 0,
"SELECT 'Column MinAudioFile already exists in Controls'",
"ALTER TABLE `Controls` ADD COLUMN `MinAudioFile` int(10) unsigned default NULL AFTER `CanAudioPlay`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Checking for MaxAudioFile in Controls';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Controls'
  AND table_schema = DATABASE()
  AND column_name = 'MaxAudioFile'
  ) > 0,
"SELECT 'Column MaxAudioFile already exists in Controls'",
"ALTER TABLE `Controls` ADD COLUMN `MaxAudioFile` int(10) unsigned default NULL AFTER `MinAudioFile`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Checking for CanAudioVolume in Controls';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Controls'
  AND table_schema = DATABASE()
  AND column_name = 'CanAudioVolume'
  ) > 0,
"SELECT 'Column CanAudioVolume already exists in Controls'",
"ALTER TABLE `Controls` ADD COLUMN `CanAudioVolume` tinyint(3) unsigned NOT NULL default '0' AFTER `MaxAudioFile`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Checking for the ONVIF IP Speaker Controls entry';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM `Controls`
  WHERE `Name` = 'ONVIF IP Speaker'
  ) > 0,
"SELECT 'ONVIF IP Speaker control already exists'",
"INSERT INTO `Controls` (`Name`,`Type`,`Protocol`,`CanAudioPlay`,`MinAudioFile`,`MaxAudioFile`,`CanAudioVolume`) VALUES ('ONVIF IP Speaker','Ffmpeg','IPSpeaker',1,10,14,1)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
