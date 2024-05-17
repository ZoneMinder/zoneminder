
SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='6 Wide') > 0,
      "SELECT '6 Wide already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'6 Wide\', \'{ "default":{"float":"left", "width":"16.6%","left":"0px","right":"0px","top":"0px","bottom":"0px"} }\' );'
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='7 Wide') > 0,
      "SELECT '7 Wide already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'7 Wide\', \'{ "default":{"float":"left", "width":"14.2%","left":"0px","right":"0px","top":"0px","bottom":"0px"} }\' );'
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='8 Wide') > 0,
      "SELECT '8 Wide already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'8 Wide\', \'{ "default":{"float":"left", "width":"12.5%","left":"0px","right":"0px","top":"0px","bottom":"0px"} }\' );'
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='9 Wide') > 0,
      "SELECT '9 Wide already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'9 Wide\', \'{ "default":{"float":"left", "width":"11.1%","left":"0px","right":"0px","top":"0px","bottom":"0px"} }\' );'
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='10 Wide') > 0,
      "SELECT '10 Wide already in layouts'",
'INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES (\'10 Wide\', \'{ "default":{"float":"left", "width":"10%","left":"0px","right":"0px","top":"0px","bottom":"0px"} }\' );'
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

