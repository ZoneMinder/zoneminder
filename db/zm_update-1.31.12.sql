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

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='Freeform') > 0,
      "SELECT 'Freeform already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'Freeform\', \'{"default":{"float":"left"}}\');' 
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='2 Wide') > 0,
"SELECT '2 Wide already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'2 Wide\', \'{"default":{"float":"left","width":"49%"}}\');'
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='3 Wide') > 0,
      "SELECT '3 Wide already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'3 Wide\', \'{ "default":{"float":"left", "width":"33%"} }\');'
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='4 Wide') > 0,
      "SELECT '4 Wide already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'4 Wide\', \'{ "default":{"float":"left", "width":"24.5%"} }\');'
) );

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='5 Wide') > 0,
      "SELECT '5 Wide already in layouts'",
      "INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES ('5 Wide', '{ \"default\":{\"float\":\"left\", \"width\":\"19%\"} }' );"
) );

PREPARE stmt FROM @s;
EXECUTE stmt;
