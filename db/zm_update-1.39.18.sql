--
-- Add a composite index on Server_Stats to speed up the per-server latest-stats
-- lookup (SELECT * FROM Server_Stats WHERE ServerId=? ORDER BY TimeStamp DESC LIMIT 1),
-- which was previously a full table scan.
--

SET @s = (SELECT IF(
  (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_name = 'Server_Stats'
    AND table_schema = DATABASE()
    AND index_name = 'Server_Stats_ServerId_idx'
  ) > 0,
  "SELECT 'Server_Stats_ServerId_idx already exists on Server_Stats table'",
  "ALTER TABLE `Server_Stats` ADD INDEX `Server_Stats_ServerId_idx` (`ServerId`, `TimeStamp`)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
