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

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
