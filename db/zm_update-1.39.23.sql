--
-- Widen Coords from tinytext to text on Zones and Maps.
--
-- Coords holds a space-separated list of "x,y" points, written with two decimal
-- places by pointsToCoords()/mapCoords().  Since 1.39.2 the Zones values are
-- percentages rather than pixels, which costs up to 13 bytes per point
-- ("100.00,100.00") against the 9 a pixel pair used ("1920,1080").  tinytext
-- caps at 255 bytes, so a zone of roughly 19 points or more overflows and is
-- silently truncated on save, leaving a corrupt polygon.
--
-- 1.39.2 widens Zones itself before converting, so the Zones statement here is
-- for installations that ran that conversion before the widening was added.
-- Maps stores coordinates in the same format and is widened to match.
-- ALTER ... MODIFY to the same type is a no-op, so both are safe to re-run.
--

ALTER TABLE Zones MODIFY `Coords` TEXT NOT NULL;

-- Maps was added to zm_create.sql.in in 1.32 without a matching update script,
-- so installations older than that have never had the table.  Skip it there
-- rather than failing the whole migration.
SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = DATABASE()
   AND table_name = 'Maps') > 0,
"ALTER TABLE Maps MODIFY `Coords` TEXT NOT NULL",
"SELECT 'Table Maps does not exist, skipping'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
