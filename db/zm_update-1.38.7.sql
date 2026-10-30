--
-- This updates a 1.38.6 database to 1.38.7
--

--
-- Add a Controls entry for ZoneMinder::Control::Reolink_HTTP.
--
-- The module has shipped without a Controls row, so it could not be chosen
-- for a monitor unless one was added by hand.  Same flags as zm_create.sql.
-- Skipped when a row with this protocol already exists (one added by hand).
--
INSERT INTO `Controls` (`Name`,`Type`,`Protocol`,`CanReboot`,`CanZoom`,`CanZoomCon`,`HasZoomSpeed`,`MinZoomSpeed`,`MaxZoomSpeed`,`CanFocus`,`CanAutoFocus`,`CanFocusCon`,`HasPresets`,`NumPresets`,`HasHomePreset`,`CanSetPresets`,`CanMove`,`CanMoveDiag`,`CanMoveCon`,`CanPan`,`HasPanSpeed`,`MinPanSpeed`,`MaxPanSpeed`,`CanTilt`,`HasTiltSpeed`,`MinTiltSpeed`,`MaxTiltSpeed`)
  SELECT 'Reolink HTTP API','Ffmpeg','Reolink_HTTP',1,1,1,1,1,64,1,1,1,1,64,1,1,1,1,1,1,1,1,64,1,1,1,64 FROM DUAL
  WHERE NOT EXISTS (SELECT 1 FROM `Controls` WHERE `Protocol` = 'Reolink_HTTP');

--
-- These three hold secrets (Janus admin key, upload account password, MQTT
-- broker password) but shipped with Private = 0, so the Config API returned
-- them to System=View accounts and the web UI wrote them into every page as
-- JavaScript constants. Mark them Private so they are withheld like
-- ZM_AUTH_HASH_SECRET. refs GHSA-8g63-hqg8-82cv
--
UPDATE `Config` SET `Private` = 1
  WHERE `Name` IN ('ZM_JANUS_SECRET', 'ZM_UPLOAD_PASS', 'ZM_MQTT_PASSWORD');
