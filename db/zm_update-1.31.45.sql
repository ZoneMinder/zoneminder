--
-- This updates a 1.31.44 database to 1.31.45
--
-- Add WebSite enum to Monitor.Type
-- Add Refresh column to Monitors table

-- This is the same as the update to 1.31.43, but due to Refresh not being added to zm_create.sql.in we need to have it 
-- again in order to fix people who did a fresh install from 1.31.43 or 1.31.44.  
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'Refresh'
    ) > 0,
"SELECT 'Column Refresh exists in Monitors'",
"ALTER TABLE Monitors ADD `Refresh` int(10) unsigned default NULL AFTER `ZoneCount`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
