--
-- Add Prefix column to Storage 
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'PathPrefix'
    ) > 0,
"SELECT 'Column PathPrefix already exists in Servers'",
"ALTER TABLE Servers ADD `PathPrefix` TEXT AFTER `Hostname`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
