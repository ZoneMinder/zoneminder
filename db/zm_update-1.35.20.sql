--
-- Add SecondPath to Monitors
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'SecondPath'
    ) > 0,
"SELECT 'Column SecondPath already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `SecondPath` VARCHAR(255) AFTER `Path`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
