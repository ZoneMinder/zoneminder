--
-- This updates a 0.9.15 database to 0.9.16
--
-- Make changes to Monitor table
--
alter table Monitors change column Function OldFunction enum('None','Passive','Active','X10') NOT NULL default 'Passive';
alter table Monitors add column Function enum('None','Monitor','Modect','Record','Mocord') NOT NULL default 'Monitor';
alter table Monitors add column RunMode enum('Continuous','Triggered') NOT NULL default 'Continuous' after Function;
alter table Monitors add column Triggers set('X10') NOT NULL after RunMode;
alter table Monitors add column SectionLength int(10) unsigned not null default 600 after PostEventCount;
alter table Monitors add column FrameSkip smallint unsigned not null default 0 after SectionLength;
--
-- Update to reflect existing setup
--
update Monitors set Function = 'Monitor' where OldFunction = 'Passive';
update Monitors set Function = 'Modect' where OldFunction = 'Active';
update Monitors set Function = 'Modect' where OldFunction = 'X10';
update Monitors set RunMode = 'Triggered' where OldFunction = 'X10';
update Monitors set Triggers = 'X10' where OldFunction = 'X10';
--
-- Create the X10 triggers table
--
CREATE TABLE TriggersX10 (
  MonitorId int(10) unsigned NOT NULL default '0',
  Activation varchar(32) default NULL,
  AlarmInput varchar(32) default NULL,
  AlarmOutput varchar(32) default NULL,
  PRIMARY KEY  (MonitorId)
) TYPE=MyISAM;
--
-- Update to reflect existing setup
--
insert into TriggersX10 select Id, X10Activation, X10AlarmInput, X10AlarmOutput from Monitors where Function = 'X10';
--
-- Clean up temporary and unused columns
--
alter table Monitors drop column OldFunction ;
alter table Monitors drop column X10Activation ;
alter table Monitors drop column X10AlarmInput ;
alter table Monitors drop column X10AlarmOutput ;
--
-- Table structure for table `States`
--
CREATE TABLE States (
  Name varchar(32) NOT NULL default '',
  Definition tinytext NOT NULL,
  PRIMARY KEY  (Name)
) TYPE=MyISAM;
--
-- These are optional, but we might as well
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
