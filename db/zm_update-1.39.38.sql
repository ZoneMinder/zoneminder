--
-- This updates a 1.39.37 database to 1.39.38
--
-- Move monitors off capture methods that are going away.
--
-- NVSocket is removed in this release and Libvlc is deprecated. Both reach
-- cameras that ffmpeg reaches, so the monitors are converted rather than
-- left to fail.
--

--
-- Libvlc becomes Ffmpeg. Path already holds the full url for this type, and
-- User and Pass are merged the same way, so only the type changes.
--
UPDATE `Monitors` SET `Type` = 'Ffmpeg' WHERE `Type` = 'Libvlc';

--
-- NVSocket has no url to convert: it spoke a bespoke socket protocol to one
-- vendor's nvr. There is nothing to point ffmpeg at, so the monitor is
-- disabled instead of being left with a type the daemon no longer knows --
-- an unrecognised type is Fatal, which would take zmc down rather than skip
-- the monitor. The row is kept so its zones, groups and history survive for
-- whoever has to decide what to do with it.
--
UPDATE `Monitors` SET `Type` = 'Ffmpeg', `Capturing` = 'None' WHERE `Type` = 'NVSocket';
UPDATE `Controls` SET `Type` = 'Ffmpeg' WHERE `Type` = 'NVSocket';
UPDATE `MonitorPresets` SET `Type` = 'Ffmpeg' WHERE `Type` = 'NVSocket';

--
-- Now that no row uses it, drop the value from the three enums that carry it.
-- Libvlc stays: it is deprecated, not removed, and a monitor can still be set
-- to it by hand.
--
ALTER TABLE `Monitors`
  MODIFY `Type` enum('Local','Remote','File','Ffmpeg','Libvlc','cURL','WebSite','VNC') NOT NULL default 'Local';
ALTER TABLE `MonitorPresets`
  MODIFY `Type` enum('Local','Remote','File','Ffmpeg','Libvlc','cURL','WebSite','VNC') NOT NULL default 'Local';
ALTER TABLE `Controls`
  MODIFY `Type` enum('Local','Remote','File','Ffmpeg','Libvlc','cURL','WebSite','VNC') NOT NULL default 'Local';
