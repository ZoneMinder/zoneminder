--
-- Update Monitors table to have SectionLengthWarn
--

SELECT 'Checking for SectionLengthWarn in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'SectionLengthWarn'
  ) > 0,
"SELECT 'Column SectionLengthWarn already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `SectionLengthWarn` boolean NOT NULL default true AFTER `SectionLength`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
