SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Monitor_Status'
    ) > 0
    ,
    "SELECT 'Monitor_Status Already exists'",
    "
CREATE TABLE `Monitor_Status` (
  `Id` int(10) unsigned NOT NULL,
  `Status`  enum('Unknown','NotRunning','Running','NoSignal','Signal') NOT NULL default 'Unknown',
  `CaptureFPS`  DECIMAL(10,2) NOT NULL default 0,
  `AnalysisFPS`  DECIMAL(5,2) NOT NULL default 0,
  PRIMARY KEY (`Id`)
) ENGINE=MEMORY"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Monitors'
      AND column_name = 'Status'
    ) > 0
    ,
    "ALTER TABLE Monitors DROP COLUMN Status",
    "SELECT 'Monitor Status already removed.'"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Monitors'
      AND column_name = 'CaptureFPS'
    ) > 0
    ,
    "ALTER TABLE Monitors DROP COLUMN CaptureFPS",
    "SELECT 'Monitor CaptureFPS already removed.'"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Monitors'
      AND column_name = 'AnalysisFPS'
    ) > 0
    ,
    "ALTER TABLE Monitors DROP COLUMN AnalysisFPS",
    "SELECT 'Monitor AnalysisFPS already removed.'"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;
