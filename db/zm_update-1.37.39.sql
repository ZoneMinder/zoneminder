SELECT 'Checking for Deleted in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'Deleted'
  ) > 0,
"SELECT 'Column Deleted already exists in Monitors'",
"ALTER TABLE Monitors ADD `Deleted` BOOLEAN NOT NULL DEFAULT false AFTER `Name`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
