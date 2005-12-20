--
-- This updates a 1.18.1 database to 1.19.0
--
-- Make changes to Frames table
--
alter table Frames add column Type enum('Normal','Bulk','Alarm') NOT NULL default 'Normal' after FrameId;
update Frames set Type = 'Alarm' where AlarmFrame = 1;
alter table Frames drop column AlarmFrame;
--
-- Make changes to Filters table
update Filters set Name = concat( Name, '_', Id );
alter table Filters drop column Id;
alter table Filters add primary key(Name); 
--
-- These are optional, but we might as well
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
