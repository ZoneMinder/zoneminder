--
-- This updates a 1.19.1 database to 1.19.2
--
-- Make changes to Events table
--
alter table Events add column Executed tinyint(3) unsigned not null default 0 after Messaged;
--
-- Make changes to Filters table
--
alter table Filters add column AutoExecute tinytext default '';
--
-- Add in a sample filter to purge the oldest 5 events when the disk is 99% full, delete is disabled though
--
insert into Filters values ('PurgeWhenFull','trms=2&obr1=&cbr1=&attr1=Archived&op1=&val1=0&cnj2=and&obr2=&cbr2=&attr2=DiskPercent&op2=>=&val2=99&sort_field=Id&sort_asc=1&limit=5',0,0,0,0,0,'');
--
-- These are optional, but we might as well
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
