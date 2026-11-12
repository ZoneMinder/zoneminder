--
-- This updates a 1.39.45 database to 1.39.46
--

--
-- The stock Update DiskSpace filter is no longer needed.
--

DELETE FROM `Filters` WHERE `Name` = 'Update DiskSpace' AND `UpdateDiskSpace` = 1;
