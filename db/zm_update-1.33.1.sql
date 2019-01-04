--
-- This updates a 1.33.0 database to 1.33.1
--
-- Add WebSite enum to Monitor.Type
-- Add Refresh column to Monitors table
--

ALTER TABLE `Monitors` 
CHANGE COLUMN `Type` `Type` ENUM('Local','Remote','File','Ffmpeg','Libvlc','cURL','WebSite','NVSocket') NOT NULL DEFAULT 'Local' ;

ALTER TABLE `MonitorPresets` 
CHANGE COLUMN `Type` `Type` ENUM('Local','Remote','File','Ffmpeg','Libvlc','cURL','WebSite','NVSocket') NOT NULL DEFAULT 'Local' ;

ALTER TABLE `Controls` 
CHANGE COLUMN `Type` `Type` ENUM('Local','Remote','File','Ffmpeg','Libvlc','cURL','WebSite','NVSocket') NOT NULL DEFAULT 'Local' ;

