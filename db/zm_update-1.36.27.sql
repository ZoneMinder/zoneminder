
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
    "SELECT 'Groups_Permissions_GroupId_idx already exists on Groups_Permissions table'",
    "CREATE INDEX `Groups_Permissions_GroupId_idx` ON `Groups_Permissions` (`GroupId`)"
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
    "SELECT 'Groups_Permissions_UserId_idx already exists on Group_Permissionss table'",
    "CREATE INDEX `Groups_Permissions_UserId_idx` ON `Groups_Permissions` (`UserId`)"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;
