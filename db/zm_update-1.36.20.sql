--
-- Update MontageLayout table to have UserId
--

SELECT 'Checking for UserId in MontageLayouts';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'MontageLayouts'
  AND table_schema = DATABASE()
  AND column_name = 'UserId'
  ) > 0,
"SELECT 'Column UserId already exists in MontageLayouts'",
"ALTER TABLE `MontageLayouts` ADD COLUMN `UserId` int(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `Name`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
