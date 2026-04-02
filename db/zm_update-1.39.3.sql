
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
-- The Zones.Area column is often 0 (never populated, or not used by the C++
-- daemon which calculates polygon area at runtime from coordinates).  So we
-- compute the polygon area directly from the percentage coordinates using the
-- shoelace formula, then derive the pixel area to convert thresholds.
--
-- new_percent = old_pixel_count * 100 / zone_pixel_area
-- zone_pixel_area = pct_poly_area * monitor_width * monitor_height / 10000
--
-- Only converts zones with percentage coordinates (contain '.') that still
-- have pixel-scale threshold values (any value > 100 can't be a percentage).

DELIMITER //

DROP PROCEDURE IF EXISTS `zm_convert_zone_thresholds_to_percent` //

CREATE PROCEDURE `zm_convert_zone_thresholds_to_percent`()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE v_zone_id INT;
  DECLARE v_coords TEXT;
  DECLARE v_mon_width INT;
  DECLARE v_mon_height INT;
  DECLARE v_min_alarm, v_max_alarm DOUBLE;
  DECLARE v_min_filter, v_max_filter DOUBLE;
  DECLARE v_min_blob, v_max_blob DOUBLE;
  DECLARE v_remaining TEXT;
  DECLARE v_pair TEXT;
  DECLARE v_space_pos INT;
  DECLARE v_x, v_y DOUBLE;
  DECLARE v_prev_x, v_prev_y DOUBLE;
  DECLARE v_first_x, v_first_y DOUBLE;
  DECLARE v_pct_area DOUBLE;
  DECLARE v_pixel_area DOUBLE;
  DECLARE v_have_first INT DEFAULT FALSE;

  DECLARE cur CURSOR FOR
    SELECT z.Id, z.Coords, m.Width, m.Height,
           z.MinAlarmPixels, z.MaxAlarmPixels,
           z.MinFilterPixels, z.MaxFilterPixels,
           z.MinBlobPixels, z.MaxBlobPixels
    FROM Zones z
    JOIN Monitors m ON z.MonitorId = m.Id
    WHERE z.Coords LIKE '%.%'
      AND m.Width > 0 AND m.Height > 0
      AND (z.MinAlarmPixels > 100 OR z.MaxAlarmPixels > 100
        OR z.MinFilterPixels > 100 OR z.MaxFilterPixels > 100
        OR z.MinBlobPixels > 100 OR z.MaxBlobPixels > 100);

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN cur;

  read_loop: LOOP
    FETCH cur INTO v_zone_id, v_coords, v_mon_width, v_mon_height,
                   v_min_alarm, v_max_alarm,
                   v_min_filter, v_max_filter,
                   v_min_blob, v_max_blob;
    IF done THEN
      LEAVE read_loop;
    END IF;

    -- Calculate polygon area from percentage coordinates (shoelace formula)
    SET v_pct_area = 0.0;
    SET v_have_first = FALSE;
    SET v_remaining = TRIM(v_coords);

    coord_loop: LOOP
      IF v_remaining = '' OR v_remaining IS NULL THEN
        LEAVE coord_loop;
      END IF;

      SET v_space_pos = LOCATE(' ', v_remaining);
      IF v_space_pos > 0 THEN
        SET v_pair = LEFT(v_remaining, v_space_pos - 1);
        SET v_remaining = TRIM(SUBSTRING(v_remaining, v_space_pos + 1));
      ELSE
        SET v_pair = v_remaining;
        SET v_remaining = '';
      END IF;

      IF v_pair = '' OR v_pair = ',' THEN
        ITERATE coord_loop;
      END IF;

      SET v_x = CAST(SUBSTRING_INDEX(v_pair, ',', 1) AS DECIMAL(10,2));
      SET v_y = CAST(SUBSTRING_INDEX(v_pair, ',', -1) AS DECIMAL(10,2));

      IF NOT v_have_first THEN
        SET v_first_x = v_x;
        SET v_first_y = v_y;
        SET v_have_first = TRUE;
      ELSE
        SET v_pct_area = v_pct_area + (v_prev_x * v_y - v_x * v_prev_y);
      END IF;

      SET v_prev_x = v_x;
      SET v_prev_y = v_y;
    END LOOP coord_loop;

    -- Close polygon
    IF v_have_first THEN
      SET v_pct_area = v_pct_area + (v_prev_x * v_first_y - v_first_x * v_prev_y);
    END IF;
    SET v_pct_area = ABS(v_pct_area) / 2.0;

    -- Convert percentage-space area to pixel area
    -- pct coords are 0-100, so full frame = 100*100 = 10000
    SET v_pixel_area = v_pct_area * v_mon_width * v_mon_height / 10000.0;

    IF v_pixel_area > 0 THEN
      -- Convert each threshold: only if > 100 (still a pixel count)
      IF v_min_alarm IS NOT NULL AND v_min_alarm > 100 THEN
        SET v_min_alarm = LEAST(ROUND(v_min_alarm * 100.0 / v_pixel_area, 2), 100);
      END IF;
      IF v_max_alarm IS NOT NULL AND v_max_alarm > 100 THEN
        SET v_max_alarm = LEAST(ROUND(v_max_alarm * 100.0 / v_pixel_area, 2), 100);
      END IF;
      IF v_min_filter IS NOT NULL AND v_min_filter > 100 THEN
        SET v_min_filter = LEAST(ROUND(v_min_filter * 100.0 / v_pixel_area, 2), 100);
      END IF;
      IF v_max_filter IS NOT NULL AND v_max_filter > 100 THEN
        SET v_max_filter = LEAST(ROUND(v_max_filter * 100.0 / v_pixel_area, 2), 100);
      END IF;
      IF v_min_blob IS NOT NULL AND v_min_blob > 100 THEN
        SET v_min_blob = LEAST(ROUND(v_min_blob * 100.0 / v_pixel_area, 2), 100);
      END IF;
      IF v_max_blob IS NOT NULL AND v_max_blob > 100 THEN
        SET v_max_blob = LEAST(ROUND(v_max_blob * 100.0 / v_pixel_area, 2), 100);
      END IF;

      UPDATE Zones SET
        MinAlarmPixels = v_min_alarm,
        MaxAlarmPixels = v_max_alarm,
        MinFilterPixels = v_min_filter,
        MaxFilterPixels = v_max_filter,
        MinBlobPixels = v_min_blob,
        MaxBlobPixels = v_max_blob
      WHERE Id = v_zone_id;
    END IF;

  END LOOP read_loop;

  CLOSE cur;
END //

DELIMITER ;

CALL zm_convert_zone_thresholds_to_percent();
DROP PROCEDURE IF EXISTS `zm_convert_zone_thresholds_to_percent`;

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
