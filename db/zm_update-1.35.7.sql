SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'Latitude'
    ) > 0,
"SELECT 'Column Latitude already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Latitude`  DECIMAL(10,8) AFTER `Refresh`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'Longitude'
    ) > 0,
"SELECT 'Column Longitude already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Longitude`  DECIMAL(10,8) AFTER `Latitude`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
