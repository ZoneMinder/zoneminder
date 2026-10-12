--
-- This updates a 1.39.40 database to 1.39.41
--

--
-- Add a Controls entry for ZoneMinder::Control::Reolink_HTTP.
--
-- The module has shipped without a Controls row, so it could not be chosen
-- for a monitor unless one was added by hand.  Same flags as db/controls.sql.
-- Skipped when a row with this protocol already exists (one added by hand).
--
INSERT INTO `Controls` (`Name`,`Protocol`,`CanReboot`,`CanZoom`,`CanZoomCon`,`HasZoomSpeed`,`MinZoomSpeed`,`MaxZoomSpeed`,`CanFocus`,`CanAutoFocus`,`CanFocusCon`,`HasPresets`,`NumPresets`,`HasHomePreset`,`CanSetPresets`,`CanMove`,`CanMoveDiag`,`CanMoveCon`,`CanPan`,`HasPanSpeed`,`MinPanSpeed`,`MaxPanSpeed`,`CanTilt`,`HasTiltSpeed`,`MinTiltSpeed`,`MaxTiltSpeed`)
  SELECT 'Reolink HTTP API','Reolink_HTTP',1,1,1,1,1,64,1,1,1,1,64,1,1,1,1,1,1,1,1,64,1,1,1,64 FROM DUAL
  WHERE NOT EXISTS (SELECT 1 FROM `Controls` WHERE `Protocol` = 'Reolink_HTTP');
