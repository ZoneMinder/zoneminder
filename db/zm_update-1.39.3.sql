--
-- Add Profile column to Notifications table
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'Notifications'
       AND COLUMN_NAME = 'Profile'
    ) > 0,
    "SELECT 'Column Profile already exists in Notifications'",
    "ALTER TABLE `Notifications` ADD `Profile` varchar(128) DEFAULT NULL AFTER `AppVersion`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
