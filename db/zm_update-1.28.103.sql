--
-- This updates a 1.28.102 database to 1.28.103
--

--
-- Add LabelSize column to Monitors
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'LabelSize'
    ) > 0,
"SELECT 'Column LabelSize exists in Monitors'",
"ALTER TABLE Monitors ADD `LabelSize` smallint(5) unsigned NOT NULL DEFAULT '1' AFTER `LabelY`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

