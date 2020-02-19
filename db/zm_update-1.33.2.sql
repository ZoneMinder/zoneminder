--
-- This updates a 1.33.0 database to 1.33.1
--
-- Add WebSite enum to Monitor.Type
-- Add Refresh column to Monitors table
--

ALTER TABLE `Events_Hour` MODIFY DiskSpace BIGINT default NULL;
ALTER TABLE `Events_Day` MODIFY DiskSpace BIGINT default NULL;
ALTER TABLE `Events_Week` MODIFY DiskSpace BIGINT default NULL;
ALTER TABLE `Events_Month` MODIFY DiskSpace BIGINT default NULL;
ALTER TABLE `Events_Archived` MODIFY DiskSpace BIGINT default NULL;

