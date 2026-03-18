
--
-- Add Profile column to Notifications table
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'Notifications'
       AND COLUMN_NAME = 'Profile'
    ) > 0,
    "SELECT 'Column Profile already exists in Notifications'",
    "ALTER TABLE `Notifications` ADD `Profile` varchar(128) DEFAULT NULL AFTER `AppVersion`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Convert zone threshold fields (MinAlarmPixels, etc.) from pixel counts
-- to percentages of zone area, matching the coordinate percentage migration
-- done in zm_update-1.39.2.sql.
--

-- First convert existing pixel count values to percentages WHILE columns
-- are still INT (values 0-100 fit in INT; ALTER to DECIMAL would fail on
-- large pixel counts that exceed DECIMAL(10,2) max of 99999.99).
--
-- Zone pixel area = (Zones.Area * Monitors.Width * Monitors.Height) / 10000
-- where Zones.Area is in percentage-space (0-10000) from zm_update-1.39.2.sql.
-- new_percent = old_pixel_count * 100 / zone_pixel_area
--             = old_pixel_count * 1000000 / (Zones.Area * Monitors.Width * Monitors.Height)
--
-- Only convert zones with percentage coordinates (contain '.') that still have
-- pixel-scale threshold values (> 100 means it can't be a percentage).

UPDATE Zones z
  JOIN Monitors m ON z.MonitorId = m.Id
SET
  z.MinAlarmPixels = CASE
    WHEN z.MinAlarmPixels IS NULL THEN NULL
    WHEN z.MinAlarmPixels = 0 THEN 0
    WHEN z.Area > 0 AND m.Width > 0 AND m.Height > 0
      THEN LEAST(ROUND(z.MinAlarmPixels * 1000000.0 / (z.Area * m.Width * m.Height), 2), 100)
    ELSE z.MinAlarmPixels END,
  z.MaxAlarmPixels = CASE
    WHEN z.MaxAlarmPixels IS NULL THEN NULL
    WHEN z.MaxAlarmPixels = 0 THEN 0
    WHEN z.Area > 0 AND m.Width > 0 AND m.Height > 0
      THEN LEAST(ROUND(z.MaxAlarmPixels * 1000000.0 / (z.Area * m.Width * m.Height), 2), 100)
    ELSE z.MaxAlarmPixels END,
  z.MinFilterPixels = CASE
    WHEN z.MinFilterPixels IS NULL THEN NULL
    WHEN z.MinFilterPixels = 0 THEN 0
    WHEN z.Area > 0 AND m.Width > 0 AND m.Height > 0
      THEN LEAST(ROUND(z.MinFilterPixels * 1000000.0 / (z.Area * m.Width * m.Height), 2), 100)
    ELSE z.MinFilterPixels END,
  z.MaxFilterPixels = CASE
    WHEN z.MaxFilterPixels IS NULL THEN NULL
    WHEN z.MaxFilterPixels = 0 THEN 0
    WHEN z.Area > 0 AND m.Width > 0 AND m.Height > 0
      THEN LEAST(ROUND(z.MaxFilterPixels * 1000000.0 / (z.Area * m.Width * m.Height), 2), 100)
    ELSE z.MaxFilterPixels END,
  z.MinBlobPixels = CASE
    WHEN z.MinBlobPixels IS NULL THEN NULL
    WHEN z.MinBlobPixels = 0 THEN 0
    WHEN z.Area > 0 AND m.Width > 0 AND m.Height > 0
      THEN LEAST(ROUND(z.MinBlobPixels * 1000000.0 / (z.Area * m.Width * m.Height), 2), 100)
    ELSE z.MinBlobPixels END,
  z.MaxBlobPixels = CASE
    WHEN z.MaxBlobPixels IS NULL THEN NULL
    WHEN z.MaxBlobPixels = 0 THEN 0
    WHEN z.Area > 0 AND m.Width > 0 AND m.Height > 0
      THEN LEAST(ROUND(z.MaxBlobPixels * 1000000.0 / (z.Area * m.Width * m.Height), 2), 100)
    ELSE z.MaxBlobPixels END
WHERE z.Coords LIKE '%.%'
  AND (z.MinAlarmPixels > 100 OR z.MaxAlarmPixels > 100
    OR z.MinFilterPixels > 100 OR z.MaxFilterPixels > 100
    OR z.MinBlobPixels > 100 OR z.MaxBlobPixels > 100);

-- Now change threshold columns from int to DECIMAL(10,2) to store percentages
-- with 2 decimal places (e.g. 25.50 = 25.50% of zone area).
-- Values are now 0-100 from the UPDATE above, so they fit in DECIMAL(10,2).

SET @s = (SELECT IF(
    (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Zones' AND column_name = 'MinAlarmPixels'
    ) = 'decimal',
"SELECT 'Zones threshold columns already DECIMAL'",
"ALTER TABLE `Zones`
  MODIFY `MinAlarmPixels` DECIMAL(10,2) unsigned default NULL,
  MODIFY `MaxAlarmPixels` DECIMAL(10,2) unsigned default NULL,
  MODIFY `MinFilterPixels` DECIMAL(10,2) unsigned default NULL,
  MODIFY `MaxFilterPixels` DECIMAL(10,2) unsigned default NULL,
  MODIFY `MinBlobPixels` DECIMAL(10,2) unsigned default NULL,
  MODIFY `MaxBlobPixels` DECIMAL(10,2) unsigned default NULL"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Also update ZonePresets table column types for consistency
SET @s = (SELECT IF(
    (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'ZonePresets' AND column_name = 'MinAlarmPixels'
    ) = 'decimal',
"SELECT 'ZonePresets threshold columns already DECIMAL'",
"ALTER TABLE `ZonePresets`
  MODIFY `MinAlarmPixels` DECIMAL(10,2) unsigned default NULL,
  MODIFY `MaxAlarmPixels` DECIMAL(10,2) unsigned default NULL,
  MODIFY `MinFilterPixels` DECIMAL(10,2) unsigned default NULL,
  MODIFY `MaxFilterPixels` DECIMAL(10,2) unsigned default NULL,
  MODIFY `MinBlobPixels` DECIMAL(10,2) unsigned default NULL,
  MODIFY `MaxBlobPixels` DECIMAL(10,2) unsigned default NULL"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Add Menu_Items table for customizable navbar/sidebar menu
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = DATABASE()
     AND table_name = 'Menu_Items'
    ) > 0,
"SELECT 'Table Menu_Items already exists'",
"CREATE TABLE `Menu_Items` (
  `Id`        int(10) unsigned NOT NULL AUTO_INCREMENT,
  `MenuKey`   varchar(32) NOT NULL,
  `Enabled`   tinyint(1) NOT NULL DEFAULT 1,
  `Label`     varchar(64) DEFAULT NULL,
  `SortOrder` smallint NOT NULL DEFAULT 0,
  `Icon`      varchar(128) DEFAULT NULL,
  `IconType`  enum('material','fontawesome','image','none') NOT NULL DEFAULT 'material',
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Menu_Items_MenuKey_idx` (`MenuKey`)
) ENGINE=InnoDB"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Seed default menu items if table is empty
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM `Menu_Items`) > 0,
"SELECT 'Menu_Items already has data'",
"INSERT INTO `Menu_Items` (`MenuKey`, `Enabled`, `SortOrder`) VALUES
  ('Console', 1, 10),
  ('Watch', 1, 15),
  ('Montage', 1, 20),
  ('MontageReview', 1, 30),
  ('Events', 1, 40),
  ('Options', 1, 50),
  ('Log', 1, 60),
  ('Devices', 1, 70),
  ('IntelGpu', 1, 80),
  ('Groups', 1, 90),
  ('Filters', 1, 100),
  ('Snapshots', 1, 110),
  ('Reports', 1, 120),
  ('ReportEventAudit', 1, 130),
  ('Map', 1, 140)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Ensure all expected menu items exist (adds any missing entries)
--

INSERT IGNORE INTO `Menu_Items` (`MenuKey`, `Enabled`, `SortOrder`) VALUES
  ('Console', 1, 10),
  ('Watch', 1, 15),
  ('Montage', 1, 20),
  ('MontageReview', 1, 30),
  ('Events', 1, 40),
  ('Options', 1, 50),
  ('Log', 1, 60),
  ('Devices', 1, 70),
  ('IntelGpu', 1, 80),
  ('Groups', 1, 90),
  ('Filters', 1, 100),
  ('Snapshots', 1, 110),
  ('Reports', 1, 120),
  ('ReportEventAudit', 1, 130),
  ('Map', 1, 140);

--
-- Add Icon and IconType columns if they don't exist
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Menu_Items' AND column_name = 'Icon'
    ) > 0,
"SELECT 'Column Icon already exists'",
"ALTER TABLE `Menu_Items` ADD `Icon` varchar(128) DEFAULT NULL AFTER `SortOrder`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Menu_Items' AND column_name = 'IconType'
    ) > 0,
"SELECT 'Column IconType already exists'",
"ALTER TABLE `Menu_Items` ADD `IconType` enum('material','fontawesome','image','none') NOT NULL DEFAULT 'material' AFTER `Icon`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
