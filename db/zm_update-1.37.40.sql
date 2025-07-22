SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'EmailFormat'
    ) > 0,
"SELECT 'Column EmailFormat already exists in Filters'",
"ALTER TABLE `Filters` ADD `EmailFormat`   enum('Individual','Summary') NOT NULL default 'Individual' AFTER `EmailBody`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
