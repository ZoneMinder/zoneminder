--
-- This updates a 1.28.99 database to 1.28.100
--

--
-- Add ServerId column to Monitors
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'StorageId'
    ) > 0,
"SELECT 'Column StorageId exists in Monitors'",
"ALTER TABLE Monitors ADD `StorageId` smallint(5) unsigned AFTER `ServerId`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

