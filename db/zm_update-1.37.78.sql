--
-- This updates a 1.37.77 database to 1.37.78
--
-- Add User Roles feature: roles define reusable permission templates
-- that provide fallback permissions when user's direct permission is 'None'
--

--
-- Table structure for table `User_Roles`
--
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = DATABASE()
      AND table_name = 'User_Roles'
    ) > 0
    ,
    "SELECT 'User_Roles table already exists.'",
    "CREATE TABLE `User_Roles` (
      `Id` int(10) unsigned NOT NULL auto_increment,
      `Name` varchar(64) NOT NULL default '',
      `Description` text,
      `Stream` enum('None','View') NOT NULL default 'None',
      `Events` enum('None','View','Edit') NOT NULL default 'None',
      `Control` enum('None','View','Edit') NOT NULL default 'None',
      `Monitors` enum('None','View','Edit','Create') NOT NULL default 'None',
      `Groups` enum('None','View','Edit') NOT NULL default 'None',
      `Devices` enum('None','View','Edit') NOT NULL default 'None',
      `Snapshots` enum('None','View','Edit') NOT NULL default 'None',
      `System` enum('None','View','Edit') NOT NULL default 'None',
      PRIMARY KEY (`Id`),
      UNIQUE KEY `UC_Name` (`Name`)
    ) ENGINE=InnoDB"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Table structure for table `Role_Groups_Permissions`
--
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = DATABASE()
      AND table_name = 'Role_Groups_Permissions'
    ) > 0
    ,
    "SELECT 'Role_Groups_Permissions table already exists.'",
    "CREATE TABLE `Role_Groups_Permissions` (
      `Id` INT(10) unsigned NOT NULL auto_increment,
      `RoleId` int(10) unsigned NOT NULL,
      `GroupId` int(10) unsigned NOT NULL,
      `Permission` enum('Inherit','None','View','Edit') NOT NULL default 'Inherit',
      PRIMARY KEY (`Id`),
      UNIQUE KEY `Role_Groups_Permissions_RoleId_GroupId_idx` (`RoleId`,`GroupId`),
      FOREIGN KEY (`RoleId`) REFERENCES `User_Roles` (`Id`) ON DELETE CASCADE,
      FOREIGN KEY (`GroupId`) REFERENCES `Groups` (`Id`) ON DELETE CASCADE
    ) ENGINE=InnoDB"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Table structure for table `Role_Monitors_Permissions`
--
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = DATABASE()
      AND table_name = 'Role_Monitors_Permissions'
    ) > 0
    ,
    "SELECT 'Role_Monitors_Permissions table already exists.'",
    "CREATE TABLE `Role_Monitors_Permissions` (
      `Id` INT(10) unsigned NOT NULL auto_increment,
      `RoleId` int(10) unsigned NOT NULL,
      `MonitorId` int(10) unsigned NOT NULL,
      `Permission` enum('Inherit','None','View','Edit') NOT NULL default 'Inherit',
      PRIMARY KEY (`Id`),
      UNIQUE KEY `Role_Monitors_Permissions_RoleId_MonitorId_idx` (`RoleId`,`MonitorId`),
      FOREIGN KEY (`RoleId`) REFERENCES `User_Roles` (`Id`) ON DELETE CASCADE,
      FOREIGN KEY (`MonitorId`) REFERENCES `Monitors` (`Id`) ON DELETE CASCADE
    ) ENGINE=InnoDB"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add RoleId column to Users table
--
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Users'
      AND column_name = 'RoleId'
    ) > 0
    ,
    "SELECT 'Users RoleId column already exists.'",
    "ALTER TABLE `Users` ADD COLUMN `RoleId` int(10) unsigned DEFAULT NULL"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add foreign key for RoleId in Users table
--
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE table_schema = DATABASE()
      AND table_name = 'Users'
      AND column_name = 'RoleId'
      AND referenced_table_name = 'User_Roles'
    ) > 0
    ,
    "SELECT 'Users RoleId foreign key already exists.'",
    "ALTER TABLE `Users` ADD FOREIGN KEY (`RoleId`) REFERENCES `User_Roles` (`Id`) ON DELETE SET NULL"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add index on RoleId for faster lookups
--
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE()
      AND table_name = 'Users'
      AND index_name = 'Users_RoleId_idx'
    ) > 0
    ,
    "SELECT 'Users RoleId index already exists.'",
    "CREATE INDEX `Users_RoleId_idx` ON `Users` (`RoleId`)"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;
