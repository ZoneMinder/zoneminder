
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Monitors'
      AND column_name = 'DecodingEnabled'
    ) > 0,
    "SELECT 'Column DecodingEnabled already exists in Monitors'",
    "ALTER TABLE Monitors ADD `DecodingEnabled` tinyint(3) unsigned NOT NULL default '1' AFTER `Enabled`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;
