--
-- This updates a 1.23.1 database to 1.23.2
--

--
-- Rename and fix typo version of PurgeWhenFull
--
update Filters set Name = "PurgeWhenFull" where Name = "xPurgeWhenFull";
update Filters set Query = 'a:4:{s:5:"terms";a:2:{i:0;a:3:{s:3:"val";s:1:"0";s:4:"attr";s:8:"Archived";s:2:"op";s:1:"=";}i:1;a:4:{s:3:"cnj";s:3:"and";s:3:"val";s:2:"95";s:4:"attr";s:11:"DiskPercent";s:2:"op";s:2:">=";}}s:10:"sort_field";s:2:"Id";s:8:"sort_asc";s:1:"1";s:5:"limit";s:2:"5";}' where Name = "PurgeWhenFull" and Query like "trms=%";

--
-- These are optional, but we might as well do it now
--
optimize table Frames;
optimize table Events;
optimize table Filters;
optimize table Zones;
optimize table Monitors;
optimize table Stats;
