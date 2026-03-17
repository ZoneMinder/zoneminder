--
-- Add Event_Video_Segments table for segmented video recording.
-- Events now record as multiple short MP4 segments instead of one
-- monolithic file, enabling instant browser playback via HLS.
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = DATABASE()
     AND table_name = 'Event_Video_Segments'
    ) > 0,
"SELECT 'Table Event_Video_Segments already exists'",
"CREATE TABLE `Event_Video_Segments` (
  `Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `EventId` bigint unsigned NOT NULL,
  `SegmentIndex` int unsigned NOT NULL DEFAULT 0,
  `Filename` varchar(128) NOT NULL DEFAULT '',
  `StartDelta` decimal(10,3) NOT NULL DEFAULT 0.000,
  `Duration` decimal(10,3) NOT NULL DEFAULT 0.000,
  `Bytes` bigint unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`),
  KEY `Event_Video_Segments_EventId_idx` (`EventId`, `SegmentIndex`),
  CONSTRAINT `Event_Video_Segments_ibfk_1` FOREIGN KEY (`EventId`) REFERENCES `Events` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
