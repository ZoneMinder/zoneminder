
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ZoneCount'
    ) > 0,
"SELECT 'Column ZoneCount already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `ZoneCount` TINYINT NOT NULL DEFAULT 0 AFTER `ArchivedEventDiskSpace`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

UPDATE Monitors SET ZoneCount=(SELECT COUNT(Id) FROM Zones WHERE MonitorId=Monitors.Id);
