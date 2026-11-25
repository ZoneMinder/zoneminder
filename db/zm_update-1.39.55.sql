--
-- This updates a 1.39.54 database to 1.39.55
--

--
-- Add CPUAffinity to Monitors: when set, the monitor's capture, decoder,
-- analysis and event threads are pinned to the set of CPUs sharing the capture
-- CPU's last-level cache, for cache locality on large/NUMA systems. Off by
-- default; it is of no benefit (and can cap throughput) on ordinary systems.
--

SELECT 'Checking for CPUAffinity in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'CPUAffinity'
  ) > 0,
"SELECT 'Column CPUAffinity already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `CPUAffinity` BOOLEAN NOT NULL DEFAULT false AFTER `Go2RTCDisableBackChannel`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
