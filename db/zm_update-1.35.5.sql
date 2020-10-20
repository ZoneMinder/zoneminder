SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'UserId'
    ) > 0,
"SELECT 'Column UserId already exists in Filters'",
"ALTER TABLE `Filters` ADD `UserId`  int(10) unsigned AFTER `Name`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
