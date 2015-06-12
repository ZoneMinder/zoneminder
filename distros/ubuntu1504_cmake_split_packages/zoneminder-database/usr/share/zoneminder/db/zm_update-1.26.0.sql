SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'Colours'
    ) > 0,
"SELECT 'Column Colours exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Colours` TINYINT UNSIGNED NOT NULL DEFAULT '1' AFTER `Height`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'Deinterlacing'
    ) > 0,
"SELECT 'Column Deinterlacing exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Deinterlacing` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `Orientation`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
