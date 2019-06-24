SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Servers'
      AND column_name = 'zmstats.pl'
    ) > 0 AND ( SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Servers'
      AND column_name = 'zmstats'
    ) = 0
    ,
    "ALTER TABLE Servers CHANGE COLUMN `zmstats.pl` `zmstats` BOOLEAN NOT NULL DEFAULT FALSE",
    "SELECT 'zmstats.pl has already been changed to zmstats'"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Servers'
      AND column_name = 'zmstats.pl'
    ) > 0,
    "ALTER TABLE Servers DROP COLUMN `zmstats.pl`",
    "SELECT 'zmstats.pl has already been removed'"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;


SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Servers'
      AND column_name = 'zmaudit.pl'
    ) > 0
    AND 
    ( SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Servers'
      AND column_name = 'zmaudit'
    ) = 0
    ,
    "ALTER TABLE Servers CHANGE COLUMN `zmaudit.pl` `zmaudit` BOOLEAN NOT NULL DEFAULT FALSE",
    "SELECT 'zmaudit.pl has already been changed to zmaudit'"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Servers'
      AND column_name = 'zmaudit.pl'
    ) > 0,
    "ALTER TABLE Servers DROP COLUMN `zmaudit.pl`",
    "SELECT 'zmaudit.pl has already been removed'"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Servers'
      AND column_name = 'zmtrigger.pl'
    ) > 0
    AND 
    ( SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Servers'
      AND column_name = 'zmtrigger'
    ) = 0
    ,
    "ALTER TABLE Servers CHANGE COLUMN `zmtrigger.pl` `zmtrigger` BOOLEAN NOT NULL DEFAULT FALSE",
    "SELECT 'zmtrigger.pl has already been changed to zmtrigger'"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Servers'
      AND column_name = 'zmtrigger.pl'
    ) > 0,
    "ALTER TABLE Servers DROP COLUMN `zmtrigger.pl`",
    "SELECT 'zmtrigger.pl has already been removed'"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;
