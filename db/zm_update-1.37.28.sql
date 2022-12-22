--
-- Rename Snapshot_Events to Snapshots_Events
--

SELECT 'Checking for Snapshot_Events table';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Snapshot_Events'
  AND table_schema = DATABASE()
  ) > 0,
"SELECT 'Snapshot_Events doesnt exist, good.'",
"ALTER TABLE `Snapshot_Events` RENAME TO Snapshots_Events`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
