
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Monitors'
      AND column_name = 'MinSectionLength'
    ) > 0,
    "SELECT 'Column MinSectionLength already exists in Monitors'",
    "ALTER TABLE Monitors ADD `MinSectionLength` int(10) unsigned NOT NULL default '10' AFTER SectionLength"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;
