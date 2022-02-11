--
-- Update Filters table to have a LockRows Column
--

SELECT 'Checking for LockRows in Filters';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Filters'
  AND table_schema = DATABASE()
  AND column_name = 'LockRows'
  ) > 0,
"SELECT 'Column LockRows already exists in Filters'",
"ALTER TABLE Filters ADD COLUMN `LockRows` tinyint(1) unsigned NOT NULL default '0' AFTER `Concurrent`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
