--
-- Add a model-specific Controls entry for the Amcrest IP5M-1190EW.
--
-- Measured with amcrest_probe.pl against firmware 2.810.00AC004.0.R.  Every
-- flag was decided by comparing frames before and after the command: this
-- firmware answers OK to codes the hardware cannot perform, and its
-- ptz.cgi?action=getStatus reports a fixed Postion=0/16.65 MoveStatus=Idle
-- whatever the camera is doing, so neither can be used to decide anything.
--
-- Pan, tilt and continuous move work.  The diagonals do not - LeftUp is
-- accepted and shifts the picture 1.8 against a 6.0 threshold.  Zoom, focus
-- and iris do not either: ZoomTele and FocusNear are accepted and change
-- nothing, autoFocus, getFocusStatus, IrisLarge and AutoPanOn are refused.
--
-- PositionABS is accepted, reaches distinct positions and reproduces a
-- revisited coordinate, so a naive test calls it working.  But its targets
-- are 90 degrees apart and the median step shifts the view 16.4 where a one
-- second nudge shifts it 42.5, so the argument is clamped to a fraction of
-- what was asked and a moveMap click would miss.  Hence CanMoveAbs and
-- CanMoveMap 0.
--
-- Speed: arg2 is accepted up to at least 64 but measured displacement stops
-- rising above 4, so the usable range is 1..4.
--
-- The white light exists in Lighting_V2, but coaxialControlIO is refused and
-- config writes to Lighting/Lighting_V2 are accepted and then ignored.
--
-- NumPresets is 25 to match the sibling Dahua/Amcrest RPC entries.  The
-- firmware stored a preset at every index tried up to 256, but this column
-- drives one button per preset in the ui and GotoPreset is refused for an
-- index that has not been stored, so a bigger number only produces buttons
-- that error until they are defined.
--
INSERT INTO `Controls`
  (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,
   `HasPresets`,`NumPresets`,`HasHomePreset`,`CanSetPresets`,
   `CanMove`,`CanMoveCon`,
   `CanPan`,`HasPanSpeed`,`MinPanSpeed`,`MaxPanSpeed`,
   `CanTilt`,`HasTiltSpeed`,`MinTiltSpeed`,`MaxTiltSpeed`)
SELECT 'Amcrest IP5M-1190EW HTTP','Ffmpeg','Amcrest_HTTP',1,1,
       1,25,1,1,
       1,1,
       1,1,1,4,
       1,1,1,4
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `Controls` WHERE `Name`='Amcrest IP5M-1190EW HTTP');
