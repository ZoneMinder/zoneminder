--
-- This adds the Event_Data Table
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Event_Data'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Event_Data table exists'",
    "
    CREATE TABLE `Event_Data` (
  `Id`      BIGINT unsigned NOT NULL auto_increment,
  `EventId` BIGINT unsigned, /* No foreign key for performance */
  `MonitorId` int(10) unsigned, /* No foreign key for performance, can be NULL */
  `FrameId`   int(10) unsigned, /* No foriegn key for performance, can by NULL */
  `Timestamp` TIMESTAMP(3),
  `Data`      TEXT,
  PRIMARY KEY (`Id`),
  KEY `Event_Data_EventId_FrameId_idx` (`EventId`, `FrameId`)
)"
  ));

PREPARE stmt FROM @s;
EXECUTE stmt;
