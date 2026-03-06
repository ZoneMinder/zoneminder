--
-- Add Notifications table for FCM push token registration
--

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'Notifications'
   AND table_schema = DATABASE()) > 0,
  "SELECT 'Notifications table already exists'",
  "CREATE TABLE `Notifications` (
    `Id`              int unsigned    NOT NULL AUTO_INCREMENT,
    `UserId`          int unsigned    NOT NULL,
    `Token`           varchar(512)    NOT NULL,
    `Platform`        enum('android','ios','web') NOT NULL,
    `MonitorList`     text            DEFAULT NULL,
    `Interval`        int unsigned    NOT NULL DEFAULT 0,
    `PushState`       enum('enabled','disabled') NOT NULL DEFAULT 'enabled',
    `AppVersion`      varchar(32)     DEFAULT NULL,
    `BadgeCount`      int             NOT NULL DEFAULT 0,
    `LastNotifiedAt`  datetime        DEFAULT NULL,
    `CreatedOn`       datetime        DEFAULT NULL,
    `UpdatedOn`       timestamp       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`Id`),
    UNIQUE KEY `Notifications_Token_idx` (`Token`),
    KEY `Notifications_UserId_idx` (`UserId`),
    CONSTRAINT `Notifications_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE CASCADE
  ) ENGINE=InnoDB"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
