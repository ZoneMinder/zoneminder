--
-- Rename Snapshot_Events to Snapshots_Events if it exists
--

SELECT 'Checking For Snapshot_Events Table which should be Snapshots_Events';
SET @s = (SELECT IF(
    ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'Snapshot_Events' AND table_schema = DATABASE()) > 0)
    AND 
    ((SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'Snapshots_Events' AND table_schema = DATABASE()) = 0),
"ALTER TABLE Snapshot_Events RENAME TO Snapshots_Events",
"SELECT 'Snapshot_Event table does not exist, good.'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
