--
-- Add StorageId column to Monitors
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Groups'
    AND table_schema = DATABASE()
    AND column_name = 'ParentId'
    ) > 0,
"SELECT 'Column GroupId exists in Groups'",
"ALTER TABLE `Groups` ADD `ParentId` int(10) unsigned AFTER `Name`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
