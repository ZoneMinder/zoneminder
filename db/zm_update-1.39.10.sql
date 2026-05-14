--
-- Add an index on Sessions.access to support session garbage collection.
--

SET @s = (SELECT IF(
  (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_name = 'Sessions'
    AND table_schema = DATABASE()
    AND index_name = 'Sessions_access_idx'
  ) > 0,
  "SELECT 'access Index already exists on Sessions table'",
  "CREATE INDEX Sessions_access_idx ON Sessions (`access`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
