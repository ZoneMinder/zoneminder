--
-- Add Cpu Usage stats to Server Status
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Server_Stats'
     AND column_name = 'CpuNicePercent'
    ) > 0,
"SELECT 'Column CpuNicePercent already exists in Server_Stats'",
"ALTER TABLE Server_Stats ADD `CpuNicePercent` DECIMAL(5,1) default NULL AFTER `CpuUserPercent`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Server_Stats'
     AND column_name = 'CpuIdlePercent'
    ) > 0,
"SELECT 'Column CpuIdlePercent already exists in Server_Stats'",
"ALTER TABLE Server_Stats ADD `CpuIdlePercent` DECIMAL(5,1) default NULL AFTER `CpuSystemPercent`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Server_Stats'
     AND column_name = 'CpuUsagePercent'
    ) > 0,
"SELECT 'Column CpuUsagePercent already exists in Server_Stats'",
"ALTER TABLE Server_Stats ADD `CpuUsagePercent` DECIMAL(5,1) default NULL AFTER `CpuIdlePercent`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
