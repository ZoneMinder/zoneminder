--
-- Update Monitors table to have Go2RTC
--

SELECT 'Checking for Go2RTCEnabled in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'Go2RTCEnabled'
  ) > 0,
"SELECT 'Column Go2RTCEnabled already exists on Monitors'",
 "ALTER TABLE `Monitors` ADD COLUMN `Go2RTCEnabled` BOOLEAN NOT NULL default false AFTER `Decoding`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Monitors'
      AND column_name = 'EncoderHWAccelName'
    ) > 0,
    "SELECT 'Column EncoderHWAccelName already exists in Monitors'",
    "ALTER TABLE Monitors ADD `EncoderHWAccelName`  varchar(64) AFTER `Encoder`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Monitors'
      AND column_name = 'EncoderHWAccelDevice'
    ) > 0,
    "SELECT 'Column EncoderHWAccelDevice already exists in Monitors'",
    "ALTER TABLE Monitors ADD `EncoderHWAccelDevice` varchar(255) AFTER `EncoderHWAccelName`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

DELETE FROM MontageLayouts WHERE Name IN ('5 Wide', '7 Wide', '9 Wide', '10 Wide');
SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='1 Wide') > 0,
      "SELECT '1 Wide already in layouts'",
"INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES ('1 Wide', NULL)"
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='2 Wide') > 0,
      "SELECT '2 Wide already in layouts'",
"INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES ('2 Wide', NULL)"
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='3 Wide') > 0,
      "SELECT '3 Wide already in layouts'",
"INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES ('3 Wide', NULL)"
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='4 Wide') > 0,
      "SELECT '4 Wide already in layouts'",
"INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES ('4 Wide', NULL)"
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='6 Wide') > 0,
      "SELECT '6 Wide already in layouts'",
"INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES ('6 Wide', NULL)"
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='8 Wide') > 0,
      "SELECT '8 Wide already in layouts'",
"INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES ('8 Wide', NULL)"
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='12 Wide') > 0,
      "SELECT '12 Wide already in layouts'",
"INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES ('12 Wide', NULL)"
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='16 Wide') > 0,
      "SELECT '16 Wide already in layouts'",
"INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES ('16 Wide', NULL)"
) );
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = ( SELECT IF(
    (SELECT COUNT(*) FROM MontageLayouts WHERE Name='24 Wide') > 0,
      "SELECT '24 Wide already in layouts'",
"INSERT INTO MontageLayouts (`Name`,`Positions`) VALUES ('24 Wide', NULL)"
) );
PREPARE stmt FROM @s;
EXECUTE stmt;
