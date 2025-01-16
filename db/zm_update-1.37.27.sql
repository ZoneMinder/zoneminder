
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
      AND index_name = 'Groups_Permissions_GroupId_UserId_idx'
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

/* User.MonitorIds can contain references to no longer existing Monitors.  So for now, drop the constraint, we will add it back at the end */
SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.table_constraints
      WHERE table_name = 'Monitors_Permissions'
      AND table_schema = DATABASE()
      AND constraint_name = 'Monitors_Permissions_ibfk_1'
    ) > 0,
    "ALTER TABLE Monitors_Permissions DROP FOREIGN KEY Monitors_Permissions_ibfk_1",
    "SELECT 'Monitors_Permissions_ibfk_1 already dropped on Monitors_Permissions table'"
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

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.STATISTICS
      WHERE table_name = 'Users'
      AND table_schema = DATABASE()
      AND column_name = 'MonitorIds'
    ) > 0,
"REPLACE INTO Monitors_Permissions (UserId,Permission, MonitorId)
   SELECT Id, 'Edit', SUBSTRING_INDEX(SUBSTRING_INDEX(Users.MonitorIds, ',', n.n), ',', -1) value FROM Users CROSS JOIN (
    SELECT a.N + b.N * 10 + 1 n FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a    ,(SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b     ORDER BY n ) n  WHERE Users.Monitors='Edit' and Users.MonitorIds != '' AND n.n <= 1 + (LENGTH(Users.MonitorIds) - LENGTH(REPLACE(Users.MonitorIds, ',', '')))  ORDER BY value",
    "SELECT 'No MonitorIds in Users'"
    ));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.STATISTICS
      WHERE table_name = 'Users'
      AND table_schema = DATABASE()
      AND column_name = 'MonitorIds'
    ) > 0,
"REPLACE INTO Monitors_Permissions (UserId,Permission, MonitorId)
   SELECT Id, 'View', SUBSTRING_INDEX(SUBSTRING_INDEX(Users.MonitorIds, ',', n.n), ',', -1) value FROM Users CROSS JOIN (
    SELECT a.N + b.N * 10 + 1 n FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a    ,(SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b     ORDER BY n ) n  WHERE Users.Monitors!='Edit' and Users.MonitorIds != '' AND n.n <= 1 + (LENGTH(Users.MonitorIds) - LENGTH(REPLACE(Users.MonitorIds, ',', '')))  ORDER BY value",
    "SELECT 'No MonitorIds in Users'"
    ));
PREPARE stmt FROM @s;
EXECUTE stmt;
DELETE FROM Monitors_Permissions WHERE MonitorID NOT IN (SELECT Id FROM Monitors);
ALTER TABLE Monitors_Permissions ADD CONSTRAINT Monitors_Permissions_ibfk_1 FOREIGN KEY (`MonitorId`) REFERENCES `Monitors` (`Id`) ON DELETE CASCADE;
