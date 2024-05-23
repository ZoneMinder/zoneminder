SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'Decoding'
    ) > 0,
"SELECT 'Column Decoding already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Decoding` enum('None','Ondemand','KeyFrames','Always') NOT NULL default 'Always' AFTER `DecodingEnabled`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

UPDATE `Monitors` SET `Decoding` = 'None' WHERE `DecodingEnabled` = 0;
UPDATE `Monitors` SET `Decoding` = 'Always' WHERE `DecodingEnabled` != 1;
