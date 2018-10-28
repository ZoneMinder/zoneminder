--
-- Add UpdateDiskSpace action to Filters
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'UpdateDiskSpace'
    ) > 0,
"SELECT 'Column UpdateDiskSpace already exists in Filters'",
"ALTER TABLE Filters ADD `UpdateDiskSpace` tinyint(3) unsigned NOT NULL default '0' AFTER `AutoDelete`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
--
-- Update Logs table to have some Indexes
--
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE table_name = 'Logs'
  AND table_schema = DATABASE()
  AND index_name = 'Logs_TimeKey_idx'
  ) > 0,
"SELECT 'Logs_TimeKey_idx already exists on Logs table'",
"CREATE INDEX `Logs_TimeKey_idx` ON `Logs` (`TimeKey`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE table_name = 'Logs'
  AND table_schema = DATABASE()
  AND index_name = 'Logs_Level_idx'
  ) > 0,
"SELECT 'Logs_Level_idx already exists on Logs table'",
"CREATE INDEX `Logs_Level_idx` ON `Logs` (`Level`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'OutputCodec'
    ) > 0,
"SELECT 'Column OutputCodec already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `OutputCodec`     enum('h264','mjpeg') AFTER `VideoWriter`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'OutputContainer'
    ) > 0,
"SELECT 'Column OutputContainer already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `OutputContainer` enum('mp4','mkv') AFTER `OutputCodec`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

