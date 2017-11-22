
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'TotalEvents'
    ) > 0,
"SELECT 'Column TotalEvents already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `TotalEvents` INT(10) AFTER `AnalysisFPS`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'HourEvents'
    ) > 0,
"SELECT 'Column HourEvents already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `HourEvents` INT(10) AFTER `TotalEvents`"
));


PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'DayEvents'
    ) > 0,
"SELECT 'Column DayEvents already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `DayEvents` INT(10) AFTER `HourEvents`"
));


PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'WeekEvents'
    ) > 0,
"SELECT 'Column WeekEvents already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `WeekEvents` INT(10) AFTER `DayEvents`"
));


PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'WeekEvents'
    ) > 0,
"SELECT 'Column MonthEvents already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `MonthEvents` INT(10) AFTER `WeekEvents`"
));


PREPARE stmt FROM @s;
EXECUTE stmt;

