--
-- Add StorageId column to Monitors
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Events'
    AND table_schema = DATABASE()
    AND column_name = 'DiskSpace'
    ) > 0,
"SELECT 'Column DiskSpace exists in Events'",
"ALTER TABLE Events ADD `DiskSpace` bigint unsigned default null AFTER `Orientation`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
