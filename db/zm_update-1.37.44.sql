--
-- This adds Tags
--

SELECT 'Checking For Tags Table';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLES
  WHERE table_name = 'Tags'
  AND table_schema = DATABASE()
  ) > 0,
  "SELECT 'Tags table exists'",
  "CREATE TABLE `Tags` (
    `Id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `Name` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
    `CreateDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `CreatedBy` int(10) unsigned,
    `LastAssignedDate` dateTime,
    PRIMARY KEY (`Id`),
    UNIQUE(`Name`)
  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SELECT 'Checking For Events_Tags Table';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLES
  WHERE table_name = 'Events_Tags'
  AND table_schema = DATABASE()
  ) > 0,
  "SELECT 'Events_Tags table exists'",
  "CREATE TABLE `Events_Tags` (
    `TagId` bigint(20) unsigned NOT NULL,
    `EventId` bigint(20) unsigned NOT NULL,
    `AssignedDate` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `AssignedBy` int(10) unsigned,
    PRIMARY KEY (`TagId`, `EventId`),
    CONSTRAINT `Events_Tags_ibfk_1` FOREIGN KEY (`TagId`) REFERENCES `Tags` (`Id`) ON DELETE CASCADE,
    CONSTRAINT `Events_Tags_ibfk_2` FOREIGN KEY (`EventId`) REFERENCES `Events` (`Id`) ON DELETE CASCADE
  ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
