--
-- This updates a 1.27.0 database to 1.27.1
--

--
-- Add Controls definition for Wanscam
--
INSERT INTO Controls 
SELECT * FROM (SELECT NULL as Id,
                     'WanscamPT' as Name,
                     'Remote' as Type,
                     'Wanscam' as Protocol,
                     1 as CanWake,
                     1 as CanSleep,
                     1 as CanReset,
                     0 as CanZoom,
                     0 as CanAutoZoom,
                     0 as CanZoomAbs,
                     0 as CanZoomRel,
                     0 as CanZoomCon,
                     0 as MinZoomRange,
                     0 as MaxZoomRange,
                     0 as MinZoomStep,
                     0 as MaxZoomStep,
                     0 as HasZoomSpeed,
                     0 as MinZoomSpeed,
                     0 as MaxZoomSpeed,
                     0 as CanFocus,
                     0 as CanAutoFocus,
                     0 as CanFocusAbs,
                     0 as CanFocusRel,
                     0 as CanFocusCon,
                     0 as MinFocusRange,
                     0 as MaxFocusRange,
                     0 as MinFocusStep,
                     0 as MaxFocusStep,
                     0 as HasFocusSpeed,
                     0 as MinFocusSpeed,
                     0 as MaxFocusSpeed,
                     1 as CanIris,
                     0 as CanAutoIris,
                     1 as CanIrisAbs,
                     0 as CanIrisRel,
                     0 as CanIrisCon,
                     0 as MinIrisRange,
                     16 as MaxIrisRange,
                     0 as MinIrisStep,
                     0 as MaxIrisStep,
                     0 as HasIrisSpeed,
                     0 as MinIrisSpeed,
                     0 as MaxIrisSpeed,
                     0 as CanGain,
                     0 as CanAutoGain,
                     0 as CanGainAbs,
                     0 as CanGainRel,
                     0 as CanGainCon,
                     0 as MinGainRange,
                     0 as MaxGainRange,
                     0 as MinGainStep,
                     0 as MaxGainStep,
                     0 as HasGainSpeed,
                     0 as MinGainSpeed,
                     0 as MaxGainSpeed,
                     1 as CanWhite,
                     0 as CanAutoWhite,
                     1 as CanWhiteAbs,
                     0 as CanWhiteRel,
                     0 as CanWhiteCon,
                     0 as MinWhiteRange,
                     16 as MaxWhiteRange,
                     0 as MinWhiteStep,
                     0 as MaxWhiteStep,
                     0 as HasWhiteSpeed,
                     0 as MinWhiteSpeed,
                     0 as MaxWhiteSpeed,
                     1 as HasPresets,
                     16 as NumPresets,
                     1 as HasHomePreset,
                     1 as CanSetPresets,
                     1 as CanMove,
                     1 as CanMoveDiag,
                     0 as CanMoveMap,
                     0 as CanMoveAbs,
                     0 as CanMoveRel,
                     1 as CanMoveCon,
                     1 as CanPan,
                     0 as MinPanRange,
                     0 as MaxPanRange,
                     0 as MinPanStep,
                     0 as MaxPanStep,
                     0 as HasPanSpeed,
                     0 as MinPanSpeed,
                     0 as MaxPanSpeed,
                     0 as HasTurboPan,
                     0 as TurboPanSpeed,
                     1 as CanTilt,
                     0 as MinTiltRange,
                     0 as MaxTiltRange,
                     0 as MinTiltStep,
                     0 as MaxTiltStep,
                     0 as HasTiltSpeed,
                     0 as MinTiltSpeed,
                     0 as MaxTiltSpeed,
                     0 as HasTurboTilt,
                     0 as TurboTiltSpeed,
                     0 as CanAutoScan,
                     0 as NumScanPaths) AS tmp
WHERE NOT EXISTS (
    SELECT Name FROM Controls WHERE name = 'WanscamPT'
) LIMIT 1;

-- Add extend alarm frame count to zone definition and Presets
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Zones'
	AND table_schema = DATABASE()
	AND column_name = 'ExtendAlarmFrames'
	) > 0,
"SELECT 'Column ExtendAlarmFrames exists in Zones'",
"ALTER TABLE `Zones` ADD `ExtendAlarmFrames` smallint(5) unsigned not null default 0 AFTER `OverloadFrames`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'ZonePresets'
	AND table_schema = DATABASE()
	AND column_name = 'ExtendAlarmFrames'
	) > 0,
"SELECT 'Column ExtendAlarmFrames exists in ZonePresets'",
"ALTER TABLE `ZonePresets` ADD `ExtendAlarmFrames` smallint(5) unsigned not null default 0 AFTER `OverloadFrames`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add MotionSkipFrame field for controlling how many frames motion detection should skip.
--

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'MotionFrameSkip'
	) > 0,
"SELECT 1",
"ALTER TABLE `Monitors` ADD `MotionFrameSkip` smallint(5) unsigned NOT NULL default '0' AFTER `FrameSkip`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add Monitor Options field; used for specifying Ffmpeg AVoptions like rtsp_transport http or libVLC options
--
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'Options'
	) > 0,
"SELECT 'Column Options already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Options` varchar(255) not null default '' AFTER `Path`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add V4LMultiBuffer and V4LCapturesPerFrame to Monitor
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'V4LMultiBuffer'
    ) > 0,
"SELECT 'Column V4LMultiBuffer exists in Monitors'",
"ALTER TABLE `Monitors` ADD `V4LMultiBuffer` tinyint(1) unsigned AFTER `Format`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'V4LCapturesPerFrame'
    ) > 0,
"SELECT 'Column V4LCapturesPerFrame exists in Monitors'",
"ALTER TABLE `Monitors` ADD `V4LCapturesPerFrame` tinyint(3) unsigned AFTER `V4LMultiBuffer`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
