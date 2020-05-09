--
-- This updates a 1.28.10 database to 1.28.99
--

--
-- Add Controls definition for ONVIF
-- Add Controls definition for FI9831W
-- Add Controls definition for FI8918W
--
INSERT INTO Controls 
SELECT * FROM (SELECT NULL as Id,
                     'ONVIF Camera' as Name,
                     'Ffmpeg' as Type,
                     'onvif' as Protocol,
                     0 as CanWake,
                     0 as CanSleep,
                     1 as CanReset,
                     1 as CanZoom,
                     0 as CanAutoZoom,
                     0 as CanZoomAbs,
                     0 as CanZoomRel,
                     1 as CanZoomCon,
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
                     255 as MaxIrisRange,
                     16 as MinIrisStep,
                     16 as MaxIrisStep,
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
                     6 as MaxWhiteRange,
                     1 as MinWhiteStep,
                     1 as MaxWhiteStep,
                     0 as HasWhiteSpeed,
                     0 as MinWhiteSpeed,
                     0 as MaxWhiteSpeed,
                     1 as HasPresets,
                     10 as NumPresets,
                     0 as HasHomePreset,
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
    SELECT Name FROM Controls WHERE name = 'ONVIF Camera'
) LIMIT 1;

INSERT INTO Controls 
SELECT * FROM (SELECT NULL as Id,
                     'Foscam 9831W' as Name,
                     'Ffmpeg' as Type,
                     'FI9831W' as Protocol,
                     0 as CanWake,
                     0 as CanSleep,
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
                     0 as CanIris,
                     0 as CanAutoIris,
                     0 as CanIrisAbs,
                     0 as CanIrisRel,
                     0 as CanIrisCon,
                     0 as MinIrisRange,
                     0 as MaxIrisRange,
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
                     0 as CanWhite,
                     0 as CanAutoWhite,
                     0 as CanWhiteAbs,
                     0 as CanWhiteRel,
                     0 as CanWhiteCon,
                     0 as MinWhiteRange,
                     0 as MaxWhiteRange,
                     0 as MinWhiteStep,
                     0 as MaxWhiteStep,
                     0 as HasWhiteSpeed,
                     0 as MinWhiteSpeed,
                     0 as MaxWhiteSpeed,
                     0 as HasPresets,
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
                     360 as MaxPanRange,
                     0 as MinPanStep,
                     360 as MaxPanStep,
                     1 as HasPanSpeed,
                     0 as MinPanSpeed,
                     4 as MaxPanSpeed,
                     0 as HasTurboPan,
                     0 as TurboPanSpeed,
                     1 as CanTilt,
                     0 as MinTiltRange,
                     90 as MaxTiltRange,
                     0 as MinTiltStep,
                     90 as MaxTiltStep,
                     0 as HasTiltSpeed,
                     0 as MinTiltSpeed,
                     0 as MaxTiltSpeed,
                     0 as HasTurboTilt,
                     0 as TurboTiltSpeed,
                     0 as CanAutoScan,
                     0 as NumScanPaths) AS tmp
WHERE NOT EXISTS (
    SELECT Name FROM Controls WHERE name = 'Foscam 9831W'
) LIMIT 1;

INSERT INTO Controls 
SELECT * FROM (SELECT NULL as Id,
                     'Foscam FI8918W' as Name,
                     'Ffmpeg' as Type,
                     'FI8918W' as Protocol,
                     0 as CanWake,
                     0 as CanSleep,
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
                     0 as CanIris,
                     0 as CanAutoIris,
                     0 as CanIrisAbs,
                     0 as CanIrisRel,
                     0 as CanIrisCon,
                     0 as MinIrisRange,
                     0 as MaxIrisRange,
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
                     0 as CanWhite,
                     0 as CanAutoWhite,
                     0 as CanWhiteAbs,
                     0 as CanWhiteRel,
                     0 as CanWhiteCon,
                     0 as MinWhiteRange,
                     0 as MaxWhiteRange,
                     0 as MinWhiteStep,
                     0 as MaxWhiteStep,
                     0 as HasWhiteSpeed,
                     0 as MinWhiteSpeed,
                     0 as MaxWhiteSpeed,
                     0 as HasPresets,
                     8 as NumPresets,
                     0 as HasHomePreset,
                     1 as CanSetPresets,
                     1 as CanMove,
                     1 as CanMoveDiag,
                     0 as CanMoveMap,
                     0 as CanMoveAbs,
                     0 as CanMoveRel,
                     1 as CanMoveCon,
                     1 as CanPan,
                     0 as MinPanRange,
                     360 as MaxPanRange,
                     0 as MinPanStep,
                     360 as MaxPanStep,
                     1 as HasPanSpeed,
                     0 as MinPanSpeed,
                     4 as MaxPanSpeed,
                     0 as HasTurboPan,
                     0 as TurboPanSpeed,
                     1 as CanTilt,
                     0 as MinTiltRange,
                     90 as MaxTiltRange,
                     0 as MinTiltStep,
                     90 as MaxTiltStep,
                     0 as HasTiltSpeed,
                     0 as MinTiltSpeed,
                     0 as MaxTiltSpeed,
                     0 as HasTurboTilt,
                     0 as TurboTiltSpeed,
                     0 as CanAutoScan,
                     0 as NumScanPaths) AS tmp
WHERE NOT EXISTS (
    SELECT Name FROM Controls WHERE name = 'Foscam FI8918W'
) LIMIT 1;

--
-- Hide USE_DEEP_STORAGE from user to prevent accidental event loss
--
UPDATE Config SET Category='hidden' WHERE Name='ZM_USE_DEEP_STORAGE';

--
-- Add Id column to State
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'States'
    AND table_schema = DATABASE()
    AND column_name = 'Id'
    ) > 0,
"SELECT 'Column Id exists in States'",
"ALTER TABLE States DROP PRIMARY KEY, ADD `Id` int(10) unsigned auto_increment NOT NULL PRIMARY KEY FIRST"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

-- PP:The States table will be updated to have a new column called IsActive
-- used to keep track of which custom state is active (if any)
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'States'
	AND table_schema = DATABASE()
	AND column_name = 'IsActive'
	) > 0,
"SELECT 'Column IsActive  exists in States'",
"ALTER TABLE `States` ADD `IsActive` tinyint(3) unsigned not null default 0 AFTER `Definition`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

-- PP:If default state does not exist, create it and set its IsActive to 1
INSERT INTO States (Name,Definition,IsActive) 
	SELECT * FROM (SELECT 'default', '', '1') AS tmp
	WHERE NOT EXISTS (
		SELECT Name FROM States WHERE Name = 'default'
	) LIMIT 1;

-- PP:Start with a sane isActive state
UPDATE States SET IsActive = '0';
UPDATE States SET IsActive = '1' WHERE Name = 'default'; 

-- PP:Finally convert States to make sure Names are unique
-- If duplicate states existed while upgrading, that is
-- very likely an error that ZM allowed earlier, so
-- we are picking up the first one and deleting the others
ALTER TABLE States ADD UNIQUE (Name);

SET @s = (SELECT IF( 
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE table_name = 'Servers'
    AND table_schema = DATABASE()
    ) > 0,
"SELECT 'Servers table exists'",
"CREATE TABLE `Servers` (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `Name` varchar(64) NOT NULL default '',
  `State_Id`    int(10) unsigned,
  PRIMARY KEY (`Id`)
)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

