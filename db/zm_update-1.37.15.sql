--
-- Update Config table to have System BOOLEAN field
--

SELECT 'Checking for System in Config';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Config'
  AND table_schema = DATABASE()
  AND column_name = 'System'
  ) > 0,
"SELECT 'Column System already exists in Config'",
"ALTER TABLE `Config` ADD COLUMN `System` BOOLEAN NOT NULL DEFAULT FALSE AFTER `Private`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;


