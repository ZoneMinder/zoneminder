--
-- Update Config table to have Private
--

SELECT 'Checking for Private in Config';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Config'
  AND table_schema = DATABASE()
  AND column_name = 'Private'
  ) > 0,
"SELECT 'Column Private already exists in Config'",
"ALTER TABLE `Config` ADD COLUMN `Private` BOOLEAN NOT NULL DEFAULT FALSE AFTER `Readonly`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;


