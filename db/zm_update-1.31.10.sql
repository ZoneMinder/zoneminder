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
     AND column_name = 'CpuLoad'
    ) > 0,
"SELECT 'Column CpuLoad already exists in Servers'",
"ALTER TABLE Servers ADD `CpuLoad` DECIMAL(5,1) default NULL AFTER `Status`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'TotalMem'
    ) > 0,
"SELECT 'Column TotalMem already exists in Servers'",
"ALTER TABLE Servers ADD `TotalMem` bigint unsigned default null AFTER `CpuLoad`"
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

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'Status'
    ) > 0,
"SELECT 'Column Status already exists in Monitors'",
"ALTER TABLE Monitors ADD `Status`  enum('Unknown','NotRunning','Running','NoSignal','Signal') NOT NULL default 'Unknown' AFTER `Sequence`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'CaptureFPS'
    ) > 0,
"SELECT 'Column CaptureFPS already exists in Monitors'",
"ALTER TABLE Monitors ADD `CaptureFPS`  DECIMAL(10,2) NOT NULL default 0 AFTER `Status`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'AnalysisFPSLimit'
    ) > 0,
"SELECT 'Column AnalysisFPSLimit already exists in Monitors'",
"ALTER TABLE Monitors CHANGE COLUMN `AnalysisFPS` `AnalysisFPSLimit` DECIMAL(5,2) default NULL"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'AnalysisFPS'
    ) > 0,
"SELECT 'Column AnalysisFPS already exists in Monitors'",
"ALTER TABLE Monitors ADD `AnalysisFPS`  DECIMAL(5,2) NOT NULL default 0 AFTER `CaptureFPS`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
