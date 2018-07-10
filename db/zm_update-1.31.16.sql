--
-- Add UpdateDiskSpace action to Filters
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'AutoMove'
    ) > 0,
"SELECT 'Column AutoMove already exists in Filters'",
"ALTER TABLE Filters ADD `AutoMove` tinyint(3) unsigned NOT NULL default '0' AFTER `AutoDelete`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'AutoMoveTo'
    ) > 0,
"SELECT 'Column AutoMoveTo already exists in Filters'",
"ALTER TABLE Filters ADD `AutoMoveTo` smallint(5) unsigned NOT NULL default '0' AFTER `AutoMove`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Groups_Monitors'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Groups_Monitors table exists'",
    "CREATE TABLE `Groups_Monitors` (
      `Id` INT(10) unsigned NOT NULL auto_increment,
      `GroupId` int(10) unsigned NOT NULL,
      `MonitorId` int(10) unsigned NOT NULL,
      PRIMARY KEY (`Id`)
    )"
  ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.STATISTICS
      WHERE table_name = 'Groups_Monitors'
      AND table_schema = DATABASE()
      AND index_name = 'Groups_Monitors_GroupId_idx'
    ) > 0,
    "SELECT 'Groups_Monitors_GroupId_idx already exists on Groups table'",
    "CREATE INDEX `Groups_Monitors_GroupId_idx` ON `Groups_Monitors` (`GroupId`)"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.STATISTICS
      WHERE table_name = 'Groups_Monitors'
      AND table_schema = DATABASE()
      AND index_name = 'Groups_Monitors_MonitorId_idx'
    ) > 0,
    "SELECT 'Groups_Monitors_MonitorId_idx already exists on Groups table'",
    "CREATE INDEX `Groups_Monitors_MonitorId_idx` ON `Groups_Monitors` (`MonitorId`)"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Groups'
     AND column_name = 'MonitorIds'
    ) > 0,
    "REPLACE INTO Groups_Monitors (GroupId,MonitorId) SELECT Id,SUBSTRING_INDEX(SUBSTRING_INDEX(t.MonitorIds, ',', n.n), ',', -1) value   FROM Groups t CROSS JOIN  (    SELECT a.N + b.N * 10 + 1 n      FROM      (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a    ,(SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b     ORDER BY n ) n  WHERE t.MonitorIds != '' AND n.n <= 1 + (LENGTH(t.MonitorIds) - LENGTH(REPLACE(t.MonitorIds, ',', '')))  ORDER BY value;",
    "SELECT 'MonitorIds has already been removed.'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Groups'
     AND column_name = 'MonitorIds'
    ) > 0,
"ALTER TABLE Groups DROP MonitorIds",
"SELECT 'MonitorIds has already been removed.'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
