
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Storage'
     AND column_name = 'DoDelete'
    ) > 0,
"SELECT 'Column DoDelete already exists in Storage'",
"ALTER TABLE `Storage` ADD `DoDelete` BOOLEAN NOT NULL default true AFTER `ServerId`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Events'
     AND column_name = 'Locked'
    ) > 0,
"SELECT 'Column Locked already exists in Events'",
"ALTER TABLE `Events` ADD `Locked` BOOLEAN NOT NULL default false AFTER `Scheme`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

