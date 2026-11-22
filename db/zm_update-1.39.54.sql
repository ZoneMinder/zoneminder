--
-- This updates a 1.39.53 database to 1.39.54
--

--
-- Add ObjectCoords to Zones: the box drawn round the smallest object that
-- should alarm, as "x1,y1 x2,y2" in percent of the frame like Coords. The zone
-- editor derives the thresholds from it and redraws it on load. NULL when none
-- was drawn. Detection does not read it.
--

SELECT 'Checking for ObjectCoords in Zones';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Zones'
  AND table_schema = DATABASE()
  AND column_name = 'ObjectCoords'
  ) > 0,
"SELECT 'Column ObjectCoords already exists in Zones'",
"ALTER TABLE `Zones` ADD COLUMN `ObjectCoords` varchar(64) default NULL"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
