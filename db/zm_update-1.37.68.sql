--
-- Update Events table to have MaxScoreFrameId
--

SELECT 'Checking for MaxScoreFrameId in Events';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Events'
  AND table_schema = DATABASE()
  AND column_name = 'MaxScoreFrameId'
  ) > 0,
"SELECT 'Column MaxScoreFrameId already exists in Events'",
 "ALTER TABLE `Events` ADD COLUMN `MaxScoreFrameId` int(10) unsigned default NULL AFTER `MaxScore`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
