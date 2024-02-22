--
-- Add Type column to Storage 
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'CpuUserPercent'
    ) > 0,
"SELECT 'Column CpuUserPercent already exists in Servers'",
"ALTER TABLE Servers ADD `CpuUserPercent` DECIMAL(5,1) default NULL AFTER `CpuLoad`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'CpuNicePercent'
    ) > 0,
"SELECT 'Column CpuNicePercent already exists in Servers'",
"ALTER TABLE Servers ADD `CpuNicePercent` DECIMAL(5,1) default NULL AFTER `CpuUserPercent`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'CpuSystemPercent'
    ) > 0,
"SELECT 'Column CpuSystemPercent already exists in Servers'",
"ALTER TABLE Servers ADD `CpuSystemPercent` DECIMAL(5,1) default NULL AFTER `CpuNicePercent`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'CpuIdlePercent'
    ) > 0,
"SELECT 'Column CpuIdlePercent already exists in Servers'",
"ALTER TABLE Servers ADD `CpuIdlePercent` DECIMAL(5,1) default NULL AFTER `CpuSystemPercent`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'CpuUsagePercent'
    ) > 0,
"SELECT 'Column CpuUsagePercent already exists in Servers'",
"ALTER TABLE Servers ADD `CpuUsagePercent` DECIMAL(5,1) default NULL AFTER `CpuIdlePercent`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
