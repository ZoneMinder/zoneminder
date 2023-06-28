SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'Decoder'
    ) > 0,
"SELECT 'Column Decoder already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Decoder` varchar(32) AFTER `Deinterlacing`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
