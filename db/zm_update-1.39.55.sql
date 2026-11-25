--
-- This updates a 1.39.54 database to 1.39.55
--

--
-- Add AutoTag and AutoTagId to Filters: a filter action that adds the tag
-- AutoTagId to every event the filter matches.
--

SELECT 'Checking for AutoTag in Filters';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Filters'
  AND table_schema = DATABASE()
  AND column_name = 'AutoTag'
  ) > 0,
"SELECT 'Column AutoTag already exists in Filters'",
"ALTER TABLE `Filters` ADD COLUMN `AutoTag` tinyint(3) unsigned NOT NULL default '0' AFTER `AutoCopyTo`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Checking for AutoTagId in Filters';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Filters'
  AND table_schema = DATABASE()
  AND column_name = 'AutoTagId'
  ) > 0,
"SELECT 'Column AutoTagId already exists in Filters'",
"ALTER TABLE `Filters` ADD COLUMN `AutoTagId` bigint(20) unsigned NOT NULL default 0 AFTER `AutoTag`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
