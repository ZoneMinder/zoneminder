

SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE table_name = 'Monitor_Status'
  AND table_schema = DATABASE()
  AND index_name = 'Monitor_Status_UpdatedOn_idx'
  ) > 0,
"SELECT 'UpdateOn Index already exists on Monitor_Status table'",
"CREATE INDEX Monitor_Status_UpdatedOn_idx on Monitor_Status(UpdatedOn)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
