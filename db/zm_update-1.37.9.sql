--
-- Update Monitors table to have JanusEnabled
--

SELECT 'Checking for JanusAudioEnabled in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'JanusAudioEnabled'
  ) > 0,
"SELECT 'Column JanusAudioEnabled already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `JanusAudioEnabled` BOOLEAN NOT NULL default false AFTER `JanusEnabled`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
