
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Storage'
      AND column_name = 'ServerId'
    ) > 0,
    "SELECT 'Column ServerId already exists in Storage'",
    "ALTER TABLE Storage ADD `ServerId` int(10) unsigned AFTER `Scheme`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;
