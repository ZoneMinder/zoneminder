--
-- Update Monitors table to have JanusEnabled
--

SELECT 'Checking for JanusEnabled in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'JanusEnabled'
  ) > 0,
"SELECT 'Column JanusEnabled already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `JanusEnabled` BOOLEAN NOT NULL default false AFTER `DecodingEnabled`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
