--
-- Add a model-specific Controls entry for the Amcrest IP5M-1190EW.
--
-- Verified against firmware 2.810.00AC004.0.R.  The generic 'Amcrest HTTP API'
-- entry advertises zoom and continuous zoom and no presets, which is backwards
-- for this model: pan, tilt, the diagonals and continuous move all physically
-- move it, arg2 really is the speed, and GotoPreset works, while zoom, focus
-- and iris do not.  ZoomTele, ZoomWide, FocusNear and FocusFar answer OK and
-- do nothing; autoFocus, getFocusStatus, IrisLarge and AutoPanOn 400.
-- PositionABS answers OK and is inert - 24 pan angles and 5 tilt values all
-- produced an identical frame - so CanMoveAbs and CanMoveMap stay 0, and
-- moveMap builds on PositionABS anyway.  The white light exists in Lighting_V2
-- but coaxialControlIO 400s and config writes to Lighting/Lighting_V2 are
-- accepted and then silently ignored, so CanLight stays 0.
--
-- NumPresets 255 is the tinyint maximum and the documented Dahua limit;
-- SetPreset was accepted and read back at every index tried up to 256.
--
INSERT INTO `Controls`
  (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,
   `HasPresets`,`NumPresets`,`HasHomePreset`,`CanSetPresets`,
   `CanMove`,`CanMoveDiag`,`CanMoveCon`,
   `CanPan`,`HasPanSpeed`,`MinPanSpeed`,`MaxPanSpeed`,
   `CanTilt`,`HasTiltSpeed`,`MinTiltSpeed`,`MaxTiltSpeed`)
SELECT 'Amcrest IP5M-1190EW HTTP','Ffmpeg','Amcrest_HTTP',1,1,
       1,255,1,1,
       1,1,1,
       1,1,1,8,
       1,1,1,8
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `Controls` WHERE `Name`='Amcrest IP5M-1190EW HTTP');
