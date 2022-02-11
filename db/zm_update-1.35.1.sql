
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Storage'
     AND column_name = 'Enabled'
    ) > 0,
"SELECT 'Column Enabled already exists in Storage'",
"ALTER TABLE `Storage` ADD `Enabled` BOOLEAN NOT NULL default true AFTER `DoDelete`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
