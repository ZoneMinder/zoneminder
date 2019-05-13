--
-- Add per user API enable/disable and ability to set a minimum issued time for tokens
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Users'
     AND column_name = 'TokenMinExpiry'
    ) > 0,
"SELECT 'Column TokenMinExpiry already exists in Users'",
"ALTER TABLE Users ADD `TokenMinExpiry` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `MonitorIds`"  
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Users'
     AND column_name = 'APIEnabled'
    ) > 0,
"SELECT 'Column APIEnabled already exists in Users'",
"ALTER TABLE Users ADD `APIEnabled` tinyint(3) UNSIGNED NOT NULL default 1 AFTER `TokenMinExpiry`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
