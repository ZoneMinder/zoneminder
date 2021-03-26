--
-- Add MaxImageBufferCount, set it to ImageBufferCount if that was large and set ImageBufferCount to 3
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'MaxImageBufferCount'
    ) > 0,
"SELECT 'Column MaxImageBufferCount already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `MaxImageBufferCount` smallint(5) unsigned NOT NULL default '0' AFTER `ImageBufferCount`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

UPDATE `Monitors` SET MaxImageBufferCount=ImageBufferCount WHERE ImageBufferCount >= 20;
UPDATE `Monitors` SET ImageBufferCount = 3;

