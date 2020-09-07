
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Storage'
     AND column_name = 'Url'
    ) > 0,
"SELECT 'Column Url already exists in Storage'",
"ALTER TABLE `Storage` ADD `Url` VARCHAR(255) default NULL AFTER `Type`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
