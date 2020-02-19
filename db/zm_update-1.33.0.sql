--
-- This updates a 1.32.3 database to 1.33.0
--
--
-- Remove DefaultView from Monitors table.
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'DefaultView'
    ) > 0,
"ALTER TABLE Monitors DROP COLUMN DefaultView",
"SELECT 'Column DefaultView no longer exists in Monitors'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

