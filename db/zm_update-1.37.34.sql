SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'Latitude'
    ) > 0,
"SELECT 'Column Latitude already exists in Servers'",
"ALTER TABLE `Servers` ADD `Latitude`  DECIMAL(10,8) AFTER `zmeventnotification`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'Longitude'
    ) > 0,
"SELECT 'Column Longitude already exists in Servers'",
"ALTER TABLE `Servers` ADD `Longitude`  DECIMAL(10,8) AFTER `Latitude`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Events'
     AND column_name = 'Latitude'
    ) > 0,
"SELECT 'Column Latitude already exists in Events'",
"ALTER TABLE `Events` ADD `Latitude`  DECIMAL(10,8) AFTER `Locked`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Events'
     AND column_name = 'Longitude'
    ) > 0,
"SELECT 'Column Longitude already exists in Events'",
"ALTER TABLE `Events` ADD `Longitude`  DECIMAL(10,8) AFTER `Latitude`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
