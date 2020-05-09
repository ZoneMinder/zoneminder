
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Controls'
      AND column_name = 'CanReboot'
    ) > 0,
    "SELECT 'Column CanReboot already exists in Controls'",
    "ALTER TABLE Controls ADD `CanReboot` tinyint(3) unsigned NOT NULL default '0' AFTER `CanReset`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;
