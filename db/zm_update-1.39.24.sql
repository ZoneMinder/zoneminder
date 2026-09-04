--
-- Drop the write-only FrameSkip column from Monitors.
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'FrameSkip'
    ) > 0,
"ALTER TABLE `Monitors` DROP COLUMN `FrameSkip`",
"SELECT 'Column FrameSkip does not exist in Monitors, nothing to drop'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
