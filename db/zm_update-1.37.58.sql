SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE table_name = 'Logs'
  AND table_schema = DATABASE()
  AND index_name = 'Logs_Component_idx'
  ) > 0,
"SELECT 'Logs_Component_idx already exists on Logs table'",
"CREATE INDEX `Logs_Component_idx` ON `Logs` (`Component`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE table_name = 'Logs'
  AND table_schema = DATABASE()
  AND index_name = 'TimeKey'
  ) > 0,
"DROP INDEX `TimeKey` ON Logs",
"SELECT 'TimeKey already removed from Logs table'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- This adds Object_Types
--

SELECT 'Checking For Object_Types Table';
SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE table_name = 'Object_Types'
    AND table_schema = DATABASE()
    ) > 0,
"SELECT 'Object_Types table exists'",
"CREATE TABLE Object_Types (
  Id  INTEGER NOT NULL AUTO_INCREMENT,
  Name  VARCHAR(32) UNIQUE,
  Human TEXT,
  PRIMARY KEY (Id)
)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
