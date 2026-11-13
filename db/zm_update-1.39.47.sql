--
-- This updates a 1.39.46 database to 1.39.47
--
-- Bring upgraded databases in line with zm_create.sql.in.
--

--
-- Monitors.Janus_Use_RTSP_Restream and Monitors.Janus_RTSP_User were renamed to
-- Restream and RTSP_User by 1.37.80, which copied their values across but left
-- the old columns behind. Nothing reads them any more. They are only dropped
-- once the replacement column exists, so no value is lost.
--

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_schema = DATABASE() AND table_name = 'Monitors' AND column_name = 'Restream') > 0
  AND
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_schema = DATABASE() AND table_name = 'Monitors' AND column_name = 'Janus_Use_RTSP_Restream') > 0,
  "ALTER TABLE `Monitors` DROP COLUMN `Janus_Use_RTSP_Restream`",
  "SELECT 'Monitors.Janus_Use_RTSP_Restream already removed'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_schema = DATABASE() AND table_name = 'Monitors' AND column_name = 'RTSP_User') > 0
  AND
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_schema = DATABASE() AND table_name = 'Monitors' AND column_name = 'Janus_RTSP_User') > 0,
  "ALTER TABLE `Monitors` DROP COLUMN `Janus_RTSP_User`",
  "SELECT 'Monitors.Janus_RTSP_User already removed'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- 1.37.31 meant to remove the foreign keys on Stats but only dropped those that
-- reference Events, so the keys on MonitorId and ZoneId survived on upgraded
-- installs under whatever name they were given. zm_create.sql.in has had none of
-- them since 1.37.31. Drop them by looking the name up, then drop the indexes
-- 1.39.28 meant to remove (MonitorId, ZoneId). The EventId_ZoneId index that
-- zm_create.sql.in has had since 2022 was never added to upgraded installs, so
-- add it and drop the EventId index it replaces.
--

SET @fk = (SELECT MIN(constraint_name) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  WHERE table_schema = DATABASE() AND table_name = 'Stats'
  AND column_name = 'MonitorId' AND referenced_table_name IS NOT NULL);
SET @s = (SELECT IF(@fk IS NOT NULL,
  CONCAT('ALTER TABLE `Stats` DROP FOREIGN KEY `', @fk, '`'),
  "SELECT 'Stats.MonitorId has no FOREIGN KEY'"));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @fk = (SELECT MIN(constraint_name) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  WHERE table_schema = DATABASE() AND table_name = 'Stats'
  AND column_name = 'ZoneId' AND referenced_table_name IS NOT NULL);
SET @s = (SELECT IF(@fk IS NOT NULL,
  CONCAT('ALTER TABLE `Stats` DROP FOREIGN KEY `', @fk, '`'),
  "SELECT 'Stats.ZoneId has no FOREIGN KEY'"));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE table_schema = DATABASE() AND table_name = 'Stats' AND index_name = 'MonitorId') > 0,
  "ALTER TABLE `Stats` DROP INDEX `MonitorId`",
  "SELECT 'Stats MonitorId INDEX already removed'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE table_schema = DATABASE() AND table_name = 'Stats' AND index_name = 'ZoneId') > 0,
  "ALTER TABLE `Stats` DROP INDEX `ZoneId`",
  "SELECT 'Stats ZoneId INDEX already removed'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE table_schema = DATABASE() AND table_name = 'Stats' AND index_name = 'EventId_ZoneId') = 0,
  "ALTER TABLE `Stats` ADD INDEX `EventId_ZoneId` (`EventId`, `ZoneId`)",
  "SELECT 'Stats EventId_ZoneId INDEX already exists'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE table_schema = DATABASE() AND table_name = 'Stats' AND index_name = 'EventId') > 0,
  "ALTER TABLE `Stats` DROP INDEX `EventId`",
  "SELECT 'Stats EventId INDEX already removed'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Index names. 1.35.21 created Snapshot_Events_SnapshotId_idx and 1.37.28
-- renamed the table but not the index, and 1.37.78 added Users_RoleId_idx
-- where zm_create.sql.in leaves the foreign key's own index, RoleId.
-- Drop and add in one ALTER so the foreign key always has an index.
--

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE table_schema = DATABASE() AND table_name = 'Snapshots_Events' AND index_name = 'Snapshot_Events_SnapshotId_idx') > 0,
  "ALTER TABLE `Snapshots_Events` DROP INDEX `Snapshot_Events_SnapshotId_idx`, ADD INDEX `Snapshots_Events_SnapshotId_idx` (`SnapshotId`)",
  "SELECT 'Snapshot_Events_SnapshotId_idx already renamed'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE table_schema = DATABASE() AND table_name = 'Users' AND index_name = 'Users_RoleId_idx') > 0
  AND
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE table_schema = DATABASE() AND table_name = 'Users' AND index_name = 'RoleId') = 0,
  "ALTER TABLE `Users` DROP INDEX `Users_RoleId_idx`, ADD INDEX `RoleId` (`RoleId`)",
  "SELECT 'Users_RoleId_idx already renamed'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Object_Types.Id was created by 1.37.57 as INTEGER, which is int(11);
-- Object_Types.sql, which zm_create.sql.in sources, declares int(10). Servers
-- that report display widths show the difference.
--

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_schema = DATABASE() AND table_name = 'Object_Types' AND column_name = 'Id'
   AND column_type = 'int(11)') > 0,
  "ALTER TABLE `Object_Types` MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT",
  "SELECT 'Object_Types.Id already int(10)'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
