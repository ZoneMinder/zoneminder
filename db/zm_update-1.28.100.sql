--
-- This updates a 1.28.10 database to 1.28.99
--

--
-- Add ServerId column to Monitors
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'ServerId'
    ) > 0,
"SELECT 'Column ServerId exists in Monitors'",
"ALTER TABLE Monitors ADD `ServerId` int(10) unsigned AFTER `Name`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

