--
-- This updates a 1.39.56 database to 1.39.57
--
-- Bring upgraded databases in line with zm_create.sql.in.
--

--
-- Zones.MonitorId has been ON DELETE CASCADE in zm_create.sql.in, but databases
-- created by an older release keep a plain foreign key, so deleting a monitor
-- that still has zones is refused. Look the key up by what it references,
-- because its name differs between installs, and replace it in one ALTER so
-- the column is never without a key.
--

SET @fk = (SELECT MIN(k.constraint_name) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
  JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS r
    ON r.constraint_schema = k.table_schema AND r.constraint_name = k.constraint_name
    AND r.table_name = k.table_name
  WHERE k.table_schema = DATABASE() AND k.table_name = 'Zones' AND k.column_name = 'MonitorId'
  AND k.referenced_table_name = 'Monitors' AND r.delete_rule <> 'CASCADE');
SET @s = (SELECT IF(@fk IS NOT NULL,
  CONCAT('ALTER TABLE `Zones` DROP FOREIGN KEY `', @fk, '`, ADD FOREIGN KEY (`MonitorId`) REFERENCES `Monitors` (`Id`) ON DELETE CASCADE'),
  "SELECT 'Zones.MonitorId foreign key already cascades or is absent'"));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- The Events_Hour, Events_Day, Events_Week, Events_Month and Events_Archived
-- tables were created with an int EventId by 1.31.x, while Events.Id is bigint
-- and zm_create.sql.in declares them bigint. Test the data type, not the column
-- type: MySQL 8 reports bigint without a display width.
--

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_schema = DATABASE() AND table_name = 'Events_Hour' AND column_name = 'EventId'
   AND data_type = 'int') > 0,
  "ALTER TABLE `Events_Hour` MODIFY `EventId` BIGINT unsigned NOT NULL",
  "SELECT 'Events_Hour.EventId already bigint'"));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_schema = DATABASE() AND table_name = 'Events_Day' AND column_name = 'EventId'
   AND data_type = 'int') > 0,
  "ALTER TABLE `Events_Day` MODIFY `EventId` BIGINT unsigned NOT NULL",
  "SELECT 'Events_Day.EventId already bigint'"));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_schema = DATABASE() AND table_name = 'Events_Week' AND column_name = 'EventId'
   AND data_type = 'int') > 0,
  "ALTER TABLE `Events_Week` MODIFY `EventId` BIGINT unsigned NOT NULL",
  "SELECT 'Events_Week.EventId already bigint'"));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_schema = DATABASE() AND table_name = 'Events_Month' AND column_name = 'EventId'
   AND data_type = 'int') > 0,
  "ALTER TABLE `Events_Month` MODIFY `EventId` BIGINT unsigned NOT NULL",
  "SELECT 'Events_Month.EventId already bigint'"));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_schema = DATABASE() AND table_name = 'Events_Archived' AND column_name = 'EventId'
   AND data_type = 'int') > 0,
  "ALTER TABLE `Events_Archived` MODIFY `EventId` BIGINT unsigned NOT NULL",
  "SELECT 'Events_Archived.EventId already bigint'"));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- zm_update-1.33.13.sql widened Monitors.MaxFPS to decimal(5,3) but
-- zm_create.sql.in kept decimal(5,2), so a fresh install rounded a rate such as
-- 0.017 to 0.02. zm_create.sql.in now has decimal(5,3); this covers an install
-- whose column is still narrower.
--

SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE table_schema = DATABASE() AND table_name = 'Monitors' AND column_name = 'MaxFPS'
   AND data_type = 'decimal' AND numeric_scale < 3) > 0,
  "ALTER TABLE `Monitors` MODIFY `MaxFPS` decimal(5,3) default NULL",
  "SELECT 'Monitors.MaxFPS already decimal(5,3)'"));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
