--
-- Add AutoUnarchive action to Filters
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'AutoUnarchive'
    ) > 0,
"SELECT 'Column AutoUunarchive already exists in Filters'",
"ALTER TABLE Filters ADD `AutoUnarchive` tinyint(3) unsigned NOT NULL default '0' AFTER `AutoArchive`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
