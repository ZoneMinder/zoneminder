--
-- Drop the write-only FrameSkip column from Monitors.
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'FrameSkip'
    ) > 0,
"ALTER TABLE `Monitors` DROP COLUMN `FrameSkip`",
"SELECT 'Column FrameSkip does not exist in Monitors, nothing to drop'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Add a Controls entry for the fixed Foscam HD cameras.
--
-- The FI98xx generation onward answers the CGIProxy.fcgi settings API, but
-- the only Foscam entries we ship are for the pan/tilt models.  A fixed
-- camera such as the FI9853EP had nothing to be set to, and without a
-- ControlId cameratool.pl cannot reach the camera to read or write its
-- settings at all.
--
-- Every movement flag is 0 because the hardware has no PTZ.  CanReboot is 1
-- because rebootSystem is accepted; verified on an FI9853EP running
-- firmware 2.22.2.15.
--
INSERT INTO `Controls`
  (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`)
SELECT 'Foscam HD (fixed)','Ffmpeg','FoscamHD',0,1
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `Controls` WHERE `Name`='Foscam HD (fixed)');
