--
-- This updates a 1.22.2 database to 1.22.3
--

--
-- Add new Background column into Filters
--
alter table Filters add column Background tinyint(1) unsigned not null default 0;

--
-- Set the Background flag for any filters currently saved with Auto tasks
--
update Filters set Background = 1 where (AutoArchive = 1 or AutoVideo = 1 or AutoUpload = 1 or AutoEmail = 1 or AutoMessage = 1 or AutoExecute = 1 or AutoDelete = 1);

--
-- Add default view column into Monitors
--
alter table Monitors add column DefaultView enum ('Events','Control') not null default 'Events' after ReturnDelay;
alter table Monitors modify LabelFormat varchar(64) NOT NULL default '%%s - %y/%m/%d %H:%M:%S';

--
-- Add device permissions column into Users, set the permissions  for existing users to
-- be the same as for Monitors as a default
--
alter table Users add column Devices enum('None','View','Edit') NOT NULL default 'None' after Monitors;
update Users set Devices = Monitors;

--
-- Increase size of Notes field in Events
--
alter table Events modify column Notes text;

--
-- Create new preset labels table
--
CREATE TABLE `ControlPresets` (
  `MonitorId` int(10) unsigned NOT NULL default '0',
  `Preset` int(10) unsigned NOT NULL default '0',
  `Label` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`MonitorId`,`Preset`) 
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Create new devices table
--
CREATE TABLE `Devices` (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `Name` tinytext NOT NULL,
  `Type` enum('X10') NOT NULL default 'X10',
  `KeyString` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`Id`),
  UNIQUE KEY `UC_Id` (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
