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
-- One entry per model, because /api/image/ptz-ctrl accepts every command on
-- every model and moves nothing on the fixed ones. Capability cannot be
-- probed from the api - code 0 means understood, not performed - so it has to
-- come from knowing the model, and an entry that overstates it hands the
-- operator a panel of buttons that silently do nothing.
--
-- Both models below are fixed turrets, measured rather than assumed:
-- panPos/tiltPos/zoomPos never moved across all eight directions and both
-- zoom directions, and image sharpness stayed inside its own noise for focus
-- and iris. On the L5BW the pan samples were checked against five no-command
-- intervals of the same length, and they overlap.
--
--   L5TW  firmware V2.0.13.260306_R2
--   L5BW  firmware V2.0.0.260723_R6
--

-- Guarded on Name: the entries share a protocol, and an installation that
-- renamed one keeps its edit.

SELECT 'Checking for the HeroSpeed L5TW Controls entry';
SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM `Controls` WHERE `Name` = 'HeroSpeed L5TW') > 0,
"SELECT 'HeroSpeed L5TW control already exists'",
"INSERT INTO `Controls` (
  `Name`,`Protocol`,`CanReset`,`CanReboot`,`CanLight`
) VALUES (
  'HeroSpeed L5TW','Herospeed',1,1,1
)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Checking for the HeroSpeed L5BW Controls entry';
SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM `Controls` WHERE `Name` = 'HeroSpeed L5BW') > 0,
"SELECT 'HeroSpeed L5BW control already exists'",
"INSERT INTO `Controls` (
  `Name`,`Protocol`,`CanReset`,`CanReboot`,`CanLight`
) VALUES (
  'HeroSpeed L5BW','Herospeed',1,1,1
)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
