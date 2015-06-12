--
-- This updates a 0.9.12 database to 0.9.13
--
CREATE TABLE Users (
  Id int(10) unsigned NOT NULL auto_increment,
  Username varchar(32) NOT NULL default '',
  Password varchar(32) NOT NULL default '',
  Enabled tinyint(3) unsigned NOT NULL default '1',
  Stream enum('None','View') NOT NULL default 'None',
  Events enum('None','View','Edit') NOT NULL default 'None',
  Monitors enum('None','View','Edit') NOT NULL default 'None',
  System enum('None','View','Edit') NOT NULL default 'None',
  MonitorIds tinytext,
  PRIMARY KEY  (Id),
  UNIQUE KEY UC_Id (Id),
  UNIQUE KEY UC_Username (Username)
) TYPE=MyISAM;
insert into Users values ('','admin',password('admin'),1,'View','Edit','Edit','Edit',NULL);
--
-- These are optional, it just seemed a good time...
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
