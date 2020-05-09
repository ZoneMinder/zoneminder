
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'SignalCheckPoints'
    ) > 0,
"SELECT 'Column SignalCheckPoints already exists in Storage'",
"ALTER TABLE `Monitors` ADD `SignalCheckPoints` INT UNSIGNED NOT NULL default '0' AFTER `DefaultScale`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
