--
-- This adds Manufacturers and Models
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Manufacturers'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Manufacturers table exists'",
    "
    CREATE TABLE `Manufacturers` (
      `Id` int(10) unsigned NOT NULL auto_increment,
      `Name`  varchar(64) NOT NULL,
      PRIMARY KEY (`Id`),
      UNIQUE KEY (`Name`)
    )"
  ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Models'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Models table exists'",
    "CREATE TABLE `Models` (
      `Id` int(10) unsigned NOT NULL auto_increment,
      `Name`  varchar(64) NOT NULL,
      `ManufacturerId` int(10),
      PRIMARY KEY (`Id`),
      UNIQUE KEY (`ManufacturerId`,`Name`)
    )"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

