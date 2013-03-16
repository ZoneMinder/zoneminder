--
-- This updates a 0.9.8 database to 0.9.9
--
update Monitors set Colours = Colours * 8;
optimize table Events;
alter table Events modify column Length numeric( 10, 2 ) not null default 0.00;
optimize table Frames;
alter table Frames add column Delta numeric( 8, 2 ) not null default 0.00 after TimeStamp;
