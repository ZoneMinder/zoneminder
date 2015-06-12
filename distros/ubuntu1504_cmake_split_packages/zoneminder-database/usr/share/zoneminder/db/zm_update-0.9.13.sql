--
-- This updates a 0.9.13 database to 0.9.14
--
CREATE TABLE Config (
  Id smallint(5) unsigned NOT NULL default '0',
  Name varchar(32) NOT NULL default '',
  Value text NOT NULL,
  Type tinytext NOT NULL,
  DefaultValue tinytext,
  Hint tinytext,
  Pattern tinytext,
  Format tinytext,
  Prompt tinytext,
  Help text,
  Category varchar(32) NOT NULL default '',
  Readonly tinyint(3) unsigned NOT NULL default '0',
  Requires text,
  PRIMARY KEY  (Name),
  UNIQUE KEY UC_Name (Name)
) TYPE=MyISAM;
--
-- These are optional, but we might as well
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
