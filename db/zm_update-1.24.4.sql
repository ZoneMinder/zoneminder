--
-- This updates a 1.24.4 database to the next version
--

--
-- Create Logs table
-- TODO - defaults to MyISAM as not easy to import selected engine
--

CREATE TABLE `Logs` (
  `TimeKey` decimal(16,6) NOT NULL,
  `Component` varchar(32) NOT NULL,
  `Pid` smallint(6) DEFAULT NULL,
  `Level` tinyint(3) NOT NULL,
  `Code` char(3) NOT NULL,
  `Message` varchar(255) NOT NULL,
  `File` varchar(255) DEFAULT NULL,
  `Line` smallint(5) unsigned DEFAULT NULL,
  KEY `TimeKey` (`TimeKey`)
) ENGINE=MyISAM;
alter table Controls modify column type enum('Local','Remote','Ffmpeg') Not NULL default 'Local';
INSERT into Controls values ('','Pelco-D','Ffmpeg','PelcoD',1,1,0,1,1,0,0,1,NULL,NULL,NULL,NULL,1,0,3,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,20,1,1,1,1,0,0,0,1,1,NULL,NULL,NULL,NULL,1,0,63,1,254,1,NULL,NULL,NULL,NULL,1,0,63,1,254,0,0);
INSERT into Controls values ('','Pelco-P','Ffmpeg','PelcoP',1,1,0,1,1,0,0,1,NULL,NULL,NULL,NULL,1,0,3,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,0,0,0,0,0,NULL,NULL,NULL,NULL,0,NULL,NULL,1,20,1,1,1,1,0,0,0,1,1,NULL,NULL,NULL,NULL,1,0,63,1,254,1,NULL,NULL,NULL,NULL,1,0,63,1,254,0,0);
--
--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
