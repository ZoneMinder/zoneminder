--
-- Add a Controls entry for Herospeed / LS Vision cameras.
--
-- These speak ONVIF too, and the existing ONVIF entry stays the right choice
-- when only pan/tilt/zoom and imaging are wanted. This entry is for what ONVIF
-- cannot reach on the hardware: the white supplement floodlight used for
-- active deterrence, and pan/tilt/zoom position read-back.
--
-- ONVIF exposes no light here at all. GetRelayOutputs and the PTZ auxiliary
-- Wiper command are both accepted by the camera and neither does anything
-- measurable; the light is dayNightMode.ledMode in /api/image/image-param.
--
-- Matched on Protocol rather than Name so a re-run cannot add a second row,
-- and so an installation that renamed the entry keeps its edit.
--

SELECT 'Checking for the Herospeed Controls entry';
SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM `Controls` WHERE `Protocol` = 'Herospeed') > 0,
"SELECT 'Herospeed control already exists'",
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
  'Herospeed / LS Vision (web api)','Herospeed',
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
