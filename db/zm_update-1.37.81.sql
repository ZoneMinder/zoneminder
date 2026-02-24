--
-- This updates a 1.37.80 database to 1.37.81
--
-- Convert Zone Coords from pixel values to percentage values (0.00-100.00)
-- so that zones are resolution-independent.
--

DELIMITER //

DROP PROCEDURE IF EXISTS `zm_update_zone_coords_to_percent` //

CREATE PROCEDURE `zm_update_zone_coords_to_percent`()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE v_zone_id INT;
  DECLARE v_coords TINYTEXT;
  DECLARE v_mon_width INT;
  DECLARE v_mon_height INT;
  DECLARE v_new_coords TEXT DEFAULT '';
  DECLARE v_pair TEXT;
  DECLARE v_x_str TEXT;
  DECLARE v_y_str TEXT;
  DECLARE v_x_pct TEXT;
  DECLARE v_y_pct TEXT;
  DECLARE v_remaining TEXT;
  DECLARE v_space_pos INT;

  DECLARE cur CURSOR FOR
    SELECT z.Id, z.Coords, m.Width, m.Height
    FROM Zones z
    JOIN Monitors m ON z.MonitorId = m.Id
    WHERE m.Width > 0 AND m.Height > 0;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN cur;

  read_loop: LOOP
    FETCH cur INTO v_zone_id, v_coords, v_mon_width, v_mon_height;
    IF done THEN
      LEAVE read_loop;
    END IF;

    -- Skip if coords already look like percentages (contain a decimal point)
    IF v_coords LIKE '%.%' THEN
      ITERATE read_loop;
    END IF;

    SET v_new_coords = '';
    SET v_remaining = TRIM(v_coords);

    -- Parse each space-separated x,y pair
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

      -- Skip empty pairs (from double spaces, trailing commas etc)
      IF v_pair = '' OR v_pair = ',' THEN
        ITERATE coord_loop;
      END IF;

      -- Split on comma
      SET v_x_str = SUBSTRING_INDEX(v_pair, ',', 1);
      SET v_y_str = SUBSTRING_INDEX(v_pair, ',', -1);

      -- Convert to percentage with 2 decimal places
      -- Use CAST to DECIMAL which always uses '.' as decimal separator (locale-independent)
      SET v_x_pct = CAST(ROUND(CAST(v_x_str AS DECIMAL(10,2)) / v_mon_width * 100, 2) AS DECIMAL(10,2));
      SET v_y_pct = CAST(ROUND(CAST(v_y_str AS DECIMAL(10,2)) / v_mon_height * 100, 2) AS DECIMAL(10,2));

      IF v_new_coords != '' THEN
        SET v_new_coords = CONCAT(v_new_coords, ' ');
      END IF;
      SET v_new_coords = CONCAT(v_new_coords, v_x_pct, ',', v_y_pct);

    END LOOP coord_loop;

    IF v_new_coords != '' THEN
      UPDATE Zones SET Coords = v_new_coords WHERE Id = v_zone_id;
    END IF;

  END LOOP read_loop;

  CLOSE cur;
END //

DELIMITER ;

CALL zm_update_zone_coords_to_percent();
DROP PROCEDURE IF EXISTS `zm_update_zone_coords_to_percent`;

-- Recalculate Area from pixel-space to percentage-space (100x100 = 10000 for full frame)
UPDATE Zones z
  JOIN Monitors m ON z.MonitorId = m.Id
  SET z.Area = ROUND(z.Area * 10000.0 / (m.Width * m.Height))
  WHERE m.Width > 0 AND m.Height > 0 AND z.Area > 0;

-- Update Units to Percent for all zones, and set as new default
UPDATE Zones SET Units = 'Percent' WHERE Units = 'Pixels';
ALTER TABLE Zones ALTER Units SET DEFAULT 'Percent';
