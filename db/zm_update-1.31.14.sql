
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
     AND column_name = 'TotalEventDiskSpace'
    ) > 0,
"SELECT 'Column TotalEventDiskSpace already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `TotalEventDiskSpace` BIGINT AFTER `TotalEvents`"
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
     AND column_name = 'HourEventDiskSpace'
    ) > 0,
"SELECT 'Column HourEventDiskSpace already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `HourEventDiskSpace` BIGINT AFTER `HourEvents`"
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
     AND column_name = 'DayEventDiskSpace'
    ) > 0,
"SELECT 'Column DayEventDiskSpace already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `DayEventDiskSpace` BIGINT AFTER `DayEvents`"
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
     AND column_name = 'WeekEventDiskSpace'
    ) > 0,
"SELECT 'Column WeekEventDiskSpace already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `WeekEventDiskSpace` BIGINT AFTER `WeekEvents`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'MonthEvents'
    ) > 0,
"SELECT 'Column MonthEvents already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `MonthEvents` INT(10) AFTER `WeekEvents`"
));


PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'MonthEventDiskSpace'
    ) > 0,
"SELECT 'Column MonthEventDiskSpace already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `MonthEventDiskSpace` BIGINT AFTER `MonthEvents`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ArchivedEvents'
    ) > 0,
"SELECT 'Column ArchivedEvents already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `ArchivedEvents` INT(10) AFTER `MonthEvents`"
));


PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ArchivedEventDiskSpace'
    ) > 0,
"SELECT 'Column ArchivedEventDiskSpace already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `ArchivedEventDiskSpace` BIGINT AFTER `ArchivedEvents`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
