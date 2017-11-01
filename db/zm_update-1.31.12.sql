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
  `Positions` LONGTEXT,
  PRIMARY KEY (`Id`)
);
"
  ));

PREPARE stmt FROM @s;
EXECUTE stmt;

DELETE FROM MontageLayouts WHERE Name IN ('Freeform','2 Wide','3 Wide','4 Wide','5 Wide');

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='Freeform') > 0,
      "SELECT 'Freeform already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'Freeform\', \'{"default":{"float":"left","position":"relative","left":"0px","right":"0px","top":"0px","bottom":"0px"}}\');' 
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='2 Wide') > 0,
"SELECT '2 Wide already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'2 Wide\', \'{"default":{"float":"left","position":"relative","width":"49%","left":"0px","right":"0px","top":"0px","bottom":"0px"}}\');'
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='3 Wide') > 0,
      "SELECT '3 Wide already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'3 Wide\', \'{"default":{"float":"left","position":"relative","width":"33%","left":"0px","right":"0px","top":"0px","bottom":"0px"}}\');'
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='4 Wide') > 0,
      "SELECT '4 Wide already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'4 Wide\', \'{"default":{"float":"left","position":"relative","width":"24.5%","left":"0px","right":"0px","top":"0px","bottom":"0px"}}\');'
) );

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='5 Wide') > 0,
      "SELECT '5 Wide already in layouts'",
      'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'5 Wide\', \'{"default":{"float":"left","position":"relative","width":"19%"}}\' );'
) );

PREPARE stmt FROM @s;
EXECUTE stmt;
