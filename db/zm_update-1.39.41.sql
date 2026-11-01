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

--
-- ZM_OPT_USE_AUTH now defaults to on for new installs. An existing install
-- keeps the value it has: zmupdate.pl -f copies Value from the database and
-- only DefaultValue changes. Record the new default here too, so that an
-- upgrade that skips -f doesn't leave the row with Value = DefaultValue = 0:
-- the C++ config loader reads only rows whose Value differs from DefaultValue
-- and would take its compiled-in default (on) while the web UI read off.
--
UPDATE `Config` SET `DefaultValue` = '1' WHERE `Name` = 'ZM_OPT_USE_AUTH';

--
-- These three hold secrets (Janus admin key, upload account password, MQTT
-- broker password) but shipped with Private = 0, so the Config API returned
-- them to System=View accounts and the web UI wrote them into every page as
-- JavaScript constants. Mark them Private so they are withheld like
-- ZM_AUTH_HASH_SECRET. refs GHSA-8g63-hqg8-82cv
--
UPDATE `Config` SET `Private` = 1
  WHERE `Name` IN ('ZM_JANUS_SECRET', 'ZM_UPLOAD_PASS', 'ZM_MQTT_PASSWORD');
