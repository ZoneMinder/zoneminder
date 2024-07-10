SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'EmailServer'
    ) > 0,
"SELECT 'Column EmailServer already exists in Filters'",
"ALTER TABLE `Filters` ADD `EmailServer` TEXT AFTER `EmailBody`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
