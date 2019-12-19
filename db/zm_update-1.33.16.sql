
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Monitors'
      AND column_name = 'Notes'
    ) > 0,
    "SELECT 'Column Notes already exists in Monitors'",
    "ALTER TABLE `Monitors` ADD `Notes` TEXT AFTER `Name`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;
