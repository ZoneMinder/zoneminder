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
