--
-- This updates a 1.39.37 database to 1.39.38
--
-- Move monitors off capture methods that are going away.
--
-- NVSocket and Libvlc are both removed in this release. Every camera they
-- reached, ffmpeg reaches, so the monitors are converted rather than left to
-- fail.
--

--
-- Libvlc becomes Ffmpeg. Path already holds the full url for this type, and
-- User and Pass are merged the same way, so only the type changes.
--
-- The Options field is deliberately left alone even though its contents are
-- now meaningless: they are libvlc switches such as --rate=1, which ffmpeg
-- will not understand. Clearing it would throw away the only record of how
-- the camera was tuned, and FfmpegCamera passes Options through
-- av_dict_parse_string, which reports unknown keys and carries on rather
-- than failing to open. Leaving it costs a log line and keeps the evidence.
--
UPDATE `Monitors` SET `Type` = 'Ffmpeg' WHERE `Type` = 'Libvlc';
UPDATE `MonitorPresets` SET `Type` = 'Ffmpeg' WHERE `Type` = 'Libvlc';

--
-- NVSocket has no url to convert: it spoke a bespoke socket protocol to one
-- vendor's nvr. There is nothing to point ffmpeg at, so the monitor is
-- disabled instead of being left with a type the daemon no longer knows --
-- an unrecognised type is Fatal, which would take zmc down rather than skip
-- the monitor. The row is kept so its zones, groups and history survive for
-- whoever has to decide what to do with it.
--
UPDATE `Monitors` SET `Type` = 'Ffmpeg', `Capturing` = 'None' WHERE `Type` = 'NVSocket';
UPDATE `MonitorPresets` SET `Type` = 'Ffmpeg' WHERE `Type` = 'NVSocket';

--
-- Now that no row uses either, drop both values from the three enums that
-- carry them.
--
ALTER TABLE `Monitors`
  MODIFY `Type` enum('Local','Remote','File','Ffmpeg','cURL','WebSite','VNC') NOT NULL default 'Local';
ALTER TABLE `MonitorPresets`
  MODIFY `Type` enum('Local','Remote','File','Ffmpeg','cURL','WebSite','VNC') NOT NULL default 'Local';

--
-- Controls.Type is dropped in 1.39.39, but databases have been seen reaching
-- this script without it already, so both statements that touch it are
-- guarded. Unguarded, the UPDATE fails with ERROR 1054 and the upgrade stops
-- here, leaving the package unconfigured.
--
set @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE table_schema = DATABASE()
                  AND table_name = 'Controls' AND column_name = 'Type');

set @sqlstmt := if(@exist > 0,
  "UPDATE `Controls` SET `Type` = 'Ffmpeg' WHERE `Type` IN ('Libvlc', 'NVSocket')",
  "SELECT 'Controls.Type has already been dropped'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

set @sqlstmt := if(@exist > 0,
  "ALTER TABLE `Controls` MODIFY `Type` enum('Local','Remote','File','Ffmpeg','cURL','WebSite','VNC') NOT NULL default 'Local'",
  "SELECT 'Controls.Type has already been dropped'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
