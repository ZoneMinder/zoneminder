--
-- Add Controls entries for Herospeed / LS Vision cameras.
--
-- Herospeed / LS Vision cameras, driven through their own web api rather than
-- ONVIF. These also speak ONVIF, and that entry stays the right choice when
-- only pan/tilt/zoom and imaging are wanted; this protocol exists for what
-- ONVIF cannot reach on the hardware: the white supplement floodlight used for
-- active deterrence (dayNightMode.ledMode in /api/image/image-param), and
-- pan/tilt/zoom position read-back.
--
-- ONVIF exposes no light here at all - GetRelayOutputs and the PTZ auxiliary
-- Wiper command are both accepted by the camera and neither does anything.
--
-- Two entries, because /api/image/ptz-ctrl accepts every command on every
-- model. A fixed camera answers code 0 to pan, zoom, focus and iris and moves
-- nothing, so capabilities cannot be probed from the api and have to come from
-- knowing the model. Measured on an L5TW (fixed turret, firmware
-- V2.0.13.260306_R2): panPos/tiltPos/zoomPos never changed, and sharpness
-- moved less than its own noise, for all six directions plus focus and iris.
-- Pointing such a camera at the ptz entry gives an operator a panel of buttons
-- that silently do nothing.
--
-- Speeds are 1-8: the device advertises no range in its api or its ui, so the
-- module clamps to the window every optType was observed to accept. No
-- absolute, relative or map move exists in the api, so those stay 0.
--

-- Guarded on Name rather than Protocol: the two entries share a protocol,
-- and an installation that renamed one keeps its edit.

SELECT 'Checking for the Herospeed ptz Controls entry';
SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM `Controls` WHERE `Name` = 'Herospeed / LS Vision (ptz)') > 0,
"SELECT 'Herospeed ptz control already exists'",
"INSERT INTO `Controls` (
  `Name`,`Protocol`,
  `CanReset`,`CanReboot`,
  `CanMove`,`CanMoveCon`,`CanMoveDiag`,`CanPan`,`CanTilt`,
  `HasPanSpeed`,`MinPanSpeed`,`MaxPanSpeed`,
  `HasTiltSpeed`,`MinTiltSpeed`,`MaxTiltSpeed`,
  `CanZoom`,`CanZoomCon`,`HasZoomSpeed`,`MinZoomSpeed`,`MaxZoomSpeed`,
  `CanFocus`,`CanFocusCon`,`CanAutoFocus`,`HasFocusSpeed`,`MinFocusSpeed`,`MaxFocusSpeed`,
  `CanIris`,`CanIrisCon`,`HasIrisSpeed`,`MinIrisSpeed`,`MaxIrisSpeed`,
  `HasPresets`,`NumPresets`,`HasHomePreset`,`CanSetPresets`,
  `CanLight`
) VALUES (
  'Herospeed / LS Vision (ptz)','Herospeed',
  1,1,
  1,1,1,1,1,
  1,1,8,
  1,1,8,
  1,1,1,1,8,
  1,1,1,1,1,8,
  1,1,1,1,8,
  1,255,1,1,
  1
)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Checking for the Herospeed fixed Controls entry';
SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM `Controls` WHERE `Name` = 'Herospeed / LS Vision (fixed, light only)') > 0,
"SELECT 'Herospeed fixed control already exists'",
"INSERT INTO `Controls` (
  `Name`,`Protocol`,`CanReset`,`CanReboot`,`CanLight`
) VALUES (
  'Herospeed / LS Vision (fixed, light only)','Herospeed',1,1,1
)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Dropping the column leaves the two shipped Pelco controls duplicated.
--
-- Each existed twice, once typed Local and once Ffmpeg, identical in every
-- other column: someone with an IP camera and a serial ptz head reasonably
-- assumed the Local row would not apply to an Ffmpeg monitor and added a
-- copy, though both always worked because nothing read the type. With the
-- column gone the pair is indistinguishable, so the later row is removed.
--
-- Pelco-D and Pelco-P are NOT merged with each other. Their capability flags
-- match, but Protocol differs and selects the control module, PelcoD.pm or
-- PelcoP.pm; they are different wire protocols that happen to have the same
-- feature set.
--
-- Monitors pointing at the row being removed are moved to the survivor
-- first, so no monitor loses its ptz. Scoped to these two names rather than
-- deduplicating the table generally: a user's own same-named controls may
-- differ in ways this cannot see, and silently merging them would discard
-- their edits.
--
UPDATE `Monitors` `m`
  JOIN `Controls` `dup` ON `m`.`ControlId` = `dup`.`Id`
  JOIN (SELECT `Name`, `Protocol`, MIN(`Id`) AS `keep_id`
          FROM `Controls`
         WHERE `Name` IN ('Pelco-D', 'Pelco-P')
         GROUP BY `Name`, `Protocol`
        HAVING COUNT(*) > 1) `k`
    ON `k`.`Name` = `dup`.`Name` AND `k`.`Protocol` <=> `dup`.`Protocol`
   SET `m`.`ControlId` = `k`.`keep_id`
 WHERE `dup`.`Id` <> `k`.`keep_id`;

DELETE `dup` FROM `Controls` `dup`
  JOIN (SELECT `Name`, `Protocol`, MIN(`Id`) AS `keep_id`
          FROM `Controls`
         WHERE `Name` IN ('Pelco-D', 'Pelco-P')
         GROUP BY `Name`, `Protocol`
        HAVING COUNT(*) > 1) `k`
    ON `k`.`Name` = `dup`.`Name` AND `k`.`Protocol` <=> `dup`.`Protocol`
 WHERE `dup`.`Id` <> `k`.`keep_id`;
