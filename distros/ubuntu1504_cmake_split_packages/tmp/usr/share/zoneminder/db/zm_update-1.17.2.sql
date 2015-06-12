--
-- This updates a 1.17.2 database to 1.18.0
--
-- Make changes to Filter table
--
alter table Zones add column CheckMethod enum('AlarmedPixels','FilteredPixels','Blobs') NOT NULL default 'Blobs' after AlarmRGB;
alter table Filters drop index FilterIDX;
alter table Filters drop column MonitorId;
update Filters set AutoArchive = 0, AutoDelete = 0, AutoUpload = 0, AutoEmail = 0, AutoMessage = 0;
--
-- These are optional, but we might as well
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
