--
-- Add primary keys for Logs and Stats tables
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Logs'
     AND column_name = 'Id'
    ) > 0,
"SELECT 'Column Id already exists in Logs'",
"ALTER TABLE `Logs` ADD COLUMN `Id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`Id`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Stats'
     AND column_name = 'Id'
    ) > 0,
"SELECT 'Column Id already exists in Stats'",
"ALTER TABLE `Stats` ADD COLUMN `Id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`Id`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
