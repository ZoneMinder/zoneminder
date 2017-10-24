--
-- Add Type column to Storage 
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'Status'
    ) > 0,
"SELECT 'Column Status already exists in Servers'",
"ALTER TABLE Servers ADD `Status`  enum('Unknown','NotRunning','Running') NOT NULL default 'Unknown' AFTER `State_Id`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'Load'
    ) > 0,
"SELECT 'Column Load already exists in Servers'",
"ALTER TABLE Servers ADD `Load` DECIMAL(5,1) default NULL AFTER `Status`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'TotalMem'
    ) > 0,
"SELECT 'Column TotalMem already exists in Servers'",
"ALTER TABLE Servers ADD `TotalMem` bigint unsigned default null AFTER `Load`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'FreeMem'
    ) > 0,
"SELECT 'Column FreeMem already exists in Servers'",
"ALTER TABLE Servers ADD `FreeMem` bigint unsigned default null AFTER `TotalMem`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'TotalSwap'
    ) > 0,
"SELECT 'Column TotalSwap already exists in Servers'",
"ALTER TABLE Servers ADD `TotalSwap` bigint unsigned default null AFTER `FreeMem`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'FreeSwap'
    ) > 0,
"SELECT 'Column FreeSwap already exists in Servers'",
"ALTER TABLE Servers ADD `FreeSwap` bigint unsigned default null AFTER `TotalSwap`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
