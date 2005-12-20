--
-- This updates a 1.19.5 database to 1.20.0
--
-- Create the Groups table
--
CREATE TABLE Groups (
  Id int(10) unsigned NOT NULL auto_increment,
  Name varchar(64) NOT NULL,
  MonitorIds tinytext NOT NULL,
  PRIMARY KEY  (Id)
) TYPE=MyISAM;
--
-- Make changes to Users table
--
alter table Users modify MonitorIds tinytext not null default '';
--
-- Make changes to Monitors table
--
alter table Monitors modify column Function enum('None','Monitor','Modect','Record','Mocord','Nodect') NOT NULL default 'Monitor';
--
-- Make changes to Events table
--
alter table Events add column Cause varchar(32) not null default '' after Name;
alter table Events add column Notes tinytext after LearnState;
--
-- Add a new index to the Events table
--
alter table Events add index (Frames);
--
-- Rationalise some of the name columns
alter table Events modify column Name varchar(64) not null;
alter table Filters modify column Name varchar(64) not null;
alter table Monitors modify column Name varchar(64) not null;
alter table States modify column Name varchar(64) not null;
alter table Zones modify column Name varchar(64) not null;
--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
