SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Groups'
      AND column_name = 'MonitorIds'
    ) > 0,
    "ALTER TABLE `Groups` MODIFY `MonitorIds` text NOT NULL",
    "SELECT 'Groups no longer has MonitorIds'"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

