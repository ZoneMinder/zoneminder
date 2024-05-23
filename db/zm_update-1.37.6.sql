--
-- Update Filters table to have a ExecuteInterval Column
--

SELECT 'Checking for ExecuteInterval in Filters';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Filters'
  AND table_schema = DATABASE()
  AND column_name = 'ExecuteInterval'
  ) > 0,
"SELECT 'Column ExecuteInterval already exists in Filters'",
"ALTER TABLE Filters ADD COLUMN `ExecuteInterval` int(10) unsigned NOT NULL default '60' AFTER `UserId`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
