--
-- Add Snapshot permission to Users
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Users'
     AND column_name = 'Snapshots'
    ) > 0,
"SELECT 'Column Snapshots already exists in Users'",
"ALTER TABLE `Users` ADD `Snapshots` enum('None','View','Edit') NOT NULL default 'None' AFTER `Devices`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

UPDATE `Users` SET `Snapshots` = `Events`;
