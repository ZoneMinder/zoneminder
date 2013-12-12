--
-- This updates a 1.26.5 database to 1.26.6
--

--
-- Add Libvlc monitor type
--

ALTER TABLE Controls modify column Type enum('Local','Remote','Ffmpeg','Libvlc') NOT NULL default 'Local';
ALTER TABLE MonitorPresets modify column Type enum('Local','Remote','File','Ffmpeg','Libvlc') NOT NULL default 'Local';
ALTER TABLE Monitors modify column Type enum('Local','Remote','File','Ffmpeg','Libvlc') NOT NULL default 'Local';
