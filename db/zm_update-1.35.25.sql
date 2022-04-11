SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'TotalEvents'
    ) > 0,
"ALTER TABLE `Monitor_Status` DROP `TotalEvents`",
"SELECT 'Column TotalEvents already removed from Monitor_Status'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'TotalEventDiskSpace'
    ) > 0,
"ALTER TABLE `Monitor_Status` DROP `TotalEventDiskSpace`",
"SELECT 'Column TotalEventDiskSpace already removed from Monitor_Status'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'HourEvents'
    ) > 0,
"ALTER TABLE `Monitor_Status` DROP `HourEvents`",
"SELECT 'Column HourEvents already removed from Monitor_Status'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'HourEventDiskSpace'
    ) > 0,
"ALTER TABLE `Monitor_Status` DROP `HourEventDiskSpace`",
"SELECT 'Column HourEventDiskSpace already removed from Monitor_Status'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'DayEvents'
    ) > 0,
"ALTER TABLE `Monitor_Status` DROP `DayEvents`",
"SELECT 'Column DayEvents already removed from Monitor_Status'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'DayEventDiskSpace'
    ) > 0,
"ALTER TABLE `Monitor_Status` DROP `DayEventDiskSpace`",
"SELECT 'Column DayEventDiskSpace already removed from Monitor_Status'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'WeekEvents'
    ) > 0,
"ALTER TABLE `Monitor_Status` DROP `WeekEvents`",
"SELECT 'Column WeekEvents already removed from Monitor_Status'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'WeekEventDiskSpace'
    ) > 0,
"ALTER TABLE `Monitor_Status` DROP `WeekEventDiskSpace`",
"SELECT 'Column WeekEventDiskSpace already removed from Monitor_Status'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'MonthEvents'
    ) > 0,
"ALTER TABLE `Monitor_Status` DROP `MonthEvents`",
"SELECT 'Column MonthEvents already removed from Monitor_Status'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'MonthEventDiskSpace'
    ) > 0,
"ALTER TABLE `Monitor_Status` DROP `MonthEventDiskSpace`",
"SELECT 'Column MonthEventDiskSpace already removed from Monitor_Status'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'ArchivedEvents'
    ) > 0,
"ALTER TABLE `Monitor_Status` DROP `ArchivedEvents`",
"SELECT 'Column ArchivedEvents already removed from Monitor_Status'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'ArchivedEventDiskSpace'
    ) > 0,
"ALTER TABLE `Monitor_Status` DROP `ArchivedEventDiskSpace`",
"SELECT 'Column ArchivedEventDiskSpace already removed from Monitor_Status'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'Importance'
    ) > 0,
"SELECT 'Column Importance already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Importance`  enum('Not','Less','Normal') AFTER `RTSPStreamName`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

