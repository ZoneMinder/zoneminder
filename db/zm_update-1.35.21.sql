--
-- Add HomeView to Users
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Users'
     AND column_name = 'HomeView'
    ) > 0,
"SELECT 'Column HomeView already exists in Users'",
"ALTER TABLE `Users` ADD `HomeView`  varchar(64) NOT NULL DEFAULT '' AFTER `APIEnabled`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Snapshots'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Snapshots table exists'",
    "CREATE TABLE Snapshots (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `Name`  VARCHAR(64),
  `Description` TEXT,
  `CreatedBy`   int(10),
  `CreatedOn`   datetime default NULL,
  PRIMARY KEY(Id)
) ENGINE=InnoDB;"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Snapshot_Events'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Snapshot_Events table exists'",
    "CREATE TABLE Snapshot_Events (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `SnapshotId` int(10) unsigned NOT NULL,
  FOREIGN KEY (`SnapshotId`) REFERENCES `Snapshots` (`Id`) ON DELETE CASCADE,
  `EventId`    bigint unsigned NOT NULL,
  FOREIGN KEY (`EventId`) REFERENCES `Events` (`Id`) ON DELETE CASCADE,
  KEY `Snapshot_Events_SnapshotId_idx` (`SnapshotId`),
  PRIMARY KEY(Id)
) ENGINE=InnoDB;"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
