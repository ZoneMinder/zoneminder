--
-- This adds Manufacturers and Models
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'MontageLayouts'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'MontageLayouts table exists'",
    "
 CREATE TABLE MontageLayouts (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `Name`    TEXT  NOT NULL,
  `Positions` JSON,
  PRIMARY KEY (`Id`)
);
"
  ));

PREPARE stmt FROM @s;
EXECUTE stmt;
