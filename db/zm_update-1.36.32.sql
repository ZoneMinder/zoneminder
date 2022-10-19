DROP TABLE Groups_Permissions;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Groups_Permissions'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Groups_Permissions table exists'",
    "CREATE TABLE `Groups_Permissions` (
      `Id` INT(10) unsigned NOT NULL auto_increment,
      `GroupId` int(10) unsigned NOT NULL,
      FOREIGN KEY (`GroupId`) REFERENCES `Groups` (`Id`) ON DELETE CASCADE,
      `UserId` int(10) unsigned NOT NULL,
      FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE CASCADE,
      `Permission` enum('Inherit', 'None','View','Edit') NOT NULL default 'Inherit',
      PRIMARY KEY (`Id`)
    )"
  ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.STATISTICS
      WHERE table_name = 'Groups_Permissions'
      AND table_schema = DATABASE()
      AND index_name = 'Groups_Permissions_GroupId_idx'
    ) > 0,
    "SELECT 'Groups_Permissions_GroupId_UserId_idx already exists on Groups_Permissions table'",
    "CREATE UNIQUE INDEX `Groups_Permissions_GroupId_UserId_idx` ON `Groups_Permissions` (`GroupId`,`UserId`)"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.STATISTICS
      WHERE table_name = 'Groups_Permissions'
      AND table_schema = DATABASE()
      AND index_name = 'Groups_Permissions_UserId_idx'
    ) > 0,
    "SELECT 'Groups_Permissions_UserId_idx already exists on Groups_Permissions table'",
    "CREATE INDEX `Groups_Permissions_UserId_idx` ON `Groups_Permissions` (`UserId`)"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Monitors_Permissions'
      AND table_schema = DATABASE()
    ) > 0,
     "SELECT 'Monitors_Permissions table exists'",
    "CREATE TABLE `Monitors_Permissions` (
    `Id` INT(10) unsigned NOT NULL auto_increment,
    `MonitorId` int(10) unsigned NOT NULL,
    FOREIGN KEY (`MonitorId`) REFERENCES `Monitors` (`Id`) ON DELETE CASCADE,
    `UserId` int(10) unsigned NOT NULL,
    FOREIGN KEY (`UserId`) REFERENCES `Users` (`Id`) ON DELETE CASCADE,
    `Permission` enum('Inherit','None','View','Edit') NOT NULL default 'Inherit',
    PRIMARY KEY (`Id`)
);"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;


SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.STATISTICS
      WHERE table_name = 'Monitors_Permissions'
      AND table_schema = DATABASE()
      AND index_name = 'Monitors_Permissions_MonitorId_UserId_idx'
    ) > 0,
    "SELECT 'Monitors_Permissions_MonitorId_UserId_idx already exists on Monitors_Permissions table'",
    "CREATE UNIQUE INDEX `Monitors_Permissions_MonitorId_UserId_idx` ON `Monitors_Permissions` (`MonitorId`,`UserId`)"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.STATISTICS
      WHERE table_name = 'Monitors_Permissions'
      AND table_schema = DATABASE()
      AND index_name = 'Monitors_Permissions_UserId_idx'
    ) > 0,
    "SELECT 'Monitors_Permissions_UserId_idx already exists on Monitors_Permissions table'",
    "CREATE INDEX `Monitors_Permissions_UserId_idx` ON `Monitors_Permissions` (`UserId`)"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

"REPLACE INTO Monitors_Permissions (UserId,Permission, MonitorId)
   SELECT Id, Monitors, SUBSTRING_INDEX(SUBSTRING_INDEX(t.MonitorIds, ',', n.n), ',', -1) value FROM Users t CROSS JOIN (
    SELECT a.N + b.N * 10 + 1 n FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a    ,(SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b     ORDER BY n ) n  WHERE t.MonitorIds != '' AND n.n <= 1 + (LENGTH(t.MonitorIds) - LENGTH(REPLACE(t.MonitorIds, ',', '')))  ORDER BY value;"
