--
-- Add Type column to Storage 
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Storage'
    AND table_schema = DATABASE()
    AND column_name = 'Type'
    ) > 0,
"SELECT 'Column Type already exists in Storage'",
"ALTER TABLE Storage ADD `Type`  enum('local','s3fs') NOT NULL default 'local' AFTER `Name`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
