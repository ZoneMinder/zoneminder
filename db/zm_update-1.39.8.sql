--
-- Add CreatedBy to Reports so canEdit() can check ownership
--

SELECT 'Checking for CreatedBy in Reports';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Reports'
  AND table_schema = DATABASE()
  AND column_name = 'CreatedBy'
  ) > 0,
"SELECT 'CreatedBy column already exists in Reports'",
"ALTER TABLE Reports ADD COLUMN `CreatedBy` int(10) unsigned AFTER `Interval`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
