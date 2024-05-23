DELETE FROM MontageLayouts WHERE Name IN ('5 Wide', '7 Wide', '9 Wide', '10 Wide');
SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='1 Wide') > 0,
      "SELECT '1 Wide already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'1 Wide\', NULL)'
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='12 Wide') > 0,
      "SELECT '12 Wide already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'12 Wide\', NULL)'
) );
PREPARE stmt FROM @s;
EXECUTE stmt;
SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='16 Wide') > 0,
      "SELECT '16 Wide already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'16 Wide\', NULL)'
) );
PREPARE stmt FROM @s;
EXECUTE stmt;


