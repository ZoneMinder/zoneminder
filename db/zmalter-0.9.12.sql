--
-- This updates a 0.9.12 database to 0.9.13
--
insert into Users values ('','admin',password('admin'),1,'View','Edit','Edit','Edit',NULL);
--
-- These are optional, it just seemed a good time...
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
