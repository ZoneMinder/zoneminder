--
-- Add EventCloseMode moved from General Config
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ModectDuringPTZ'
    ) > 0,
"SELECT 'Column ModectDuringPTZ already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `ModectDuringPTZ` tinyint(3) unsigned NOT NULL default '0' AFTER `ReturnDelay`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

UPDATE `Monitors` SET EventCloseMode = (SELECT Value from Config WHERE Name='ZM_EVENT_CLOSE_MODE');


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
