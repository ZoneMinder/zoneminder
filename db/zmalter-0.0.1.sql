alter table Monitors modify Device tinyint unsigned NOT NULL default '0';
alter table Monitors modify Format tinyint unsigned NOT NULL;
alter table Monitors drop column WarmUpCount;
alter table Monitors drop column PreEventCount;
alter table Monitors drop column PostEventCount;
alter table Monitors modify LabelFormat varchar(32) not null default '%%s - %y/%m/%d %H:%M:%S';
alter table Monitors modify LabelX smallint unsigned not null;
alter table Monitors modify LabelY smallint unsigned not null;
alter table Monitors add column ImageBufferCount smallint unsigned NOT NULL default '100';
alter table Monitors add column WarmupCount smallint unsigned NOT NULL default '25';
alter table Monitors add column PreEventCount smallint unsigned NOT NULL default '10';
alter table Monitors add column PostEventCount smallint unsigned NOT NULL default '10';
alter table Monitors add column AlarmFrameCount smallint unsigned NOT NULL default '1';
alter table Monitors add column FPSReportInterval smallint unsigned NOT NULL default '250';
alter table Monitors add column RefBlendPerc tinyint unsigned NOT NULL default '10';
update Monitors set LabelFormat = '%%s - %y/%m/%d %H:%M:%S';
update Monitors set LabelX = 0;
update Monitors set LabelY = Height-8;
alter table Events add column LearnState char(1) default '' after Archived;
CREATE TABLE Filters (
Id int(10) unsigned NOT NULL auto_increment,
MonitorId int(10) unsigned NOT NULL default '0',
Name varchar(64) NOT NULL default '',
Query text NOT NULL,
AutoDelete tinyint(4) NOT NULL default '0',
AutoUpload tinyint(4) NOT NULL default '0',
PRIMARY KEY  (Id),
UNIQUE KEY FilterIDX (MonitorId,Name)
) TYPE=MyISAM;
CREATE TABLE Stats (
MonitorId int(10) unsigned NOT NULL default '0',
ZoneId int(10) unsigned NOT NULL default '0',
EventId int(10) unsigned NOT NULL default '0',
FrameId int(10) unsigned NOT NULL default '0',
AlarmPixels int(10) unsigned NOT NULL default '0',
FilterPixels int(10) unsigned NOT NULL default '0',
BlobPixels int(10) unsigned NOT NULL default '0',
Blobs smallint(5) unsigned NOT NULL default '0',
MinBlobSize smallint(5) unsigned NOT NULL default '0',
MaxBlobSize smallint(5) unsigned NOT NULL default '0',
MinX smallint(5) unsigned NOT NULL default '0',
MaxX smallint(5) unsigned NOT NULL default '0',
MinY smallint(5) unsigned NOT NULL default '0',
MaxY smallint(5) unsigned NOT NULL default '0',
Score smallint(5) unsigned NOT NULL default '0'
) TYPE=MyISAM;
