DELIMITER //

-- This index covers MonitorId grouping, StartDateTime ranges,
-- Archived status filtering, and includes DiskSpace for summation.
CREATE INDEX IF NOT EXISTS Events_Summaries_Performance_idx
ON Events (MonitorId, StartDateTime, Archived, DiskSpace)//

-- Clean up a previous typoed version of the index
DROP INDEX IF EXISTS Events_Summaries_Perfomance_idx ON Events//

-- Hourly Events View
CREATE OR REPLACE VIEW Events_Hour AS
SELECT
    Id AS EventId,
    MonitorId,
    DiskSpace,
    StartDateTime
FROM Events
WHERE StartDateTime >= (NOW() - INTERVAL 1 HOUR)//

-- Daily Events View
CREATE OR REPLACE VIEW Events_Day AS
SELECT
    Id AS EventId,
    MonitorId,
    DiskSpace,
    StartDateTime
FROM Events
WHERE StartDateTime >= (NOW() - INTERVAL 1 DAY)//

-- Weekly Events View
CREATE OR REPLACE VIEW Events_Week AS
SELECT
    Id AS EventId,
    MonitorId,
    DiskSpace,
    StartDateTime
FROM Events
WHERE StartDateTime >= (NOW() - INTERVAL 7 DAY)//

-- Monthly Events View
CREATE OR REPLACE VIEW Events_Month AS
SELECT
    Id AS EventId,
    MonitorId,
    DiskSpace,
    StartDateTime
FROM Events
WHERE StartDateTime >= (NOW() - INTERVAL 1 MONTH)//

-- Archived Events View
CREATE OR REPLACE VIEW Events_Archived AS
SELECT
    Id AS EventId,
    MonitorId,
    DiskSpace
FROM Events
WHERE Archived = 1//

-- Event Summaries Source View (used by the refresh procedure)
CREATE OR REPLACE VIEW VIEW_Event_Summaries AS
SELECT
    MonitorId,
    -- Hour Stats
    COUNT(CASE WHEN StartDateTime >= (NOW() - INTERVAL 1 HOUR) THEN 1 END) AS HourEvents,
    COALESCE(SUM(CASE WHEN StartDateTime >= (NOW() - INTERVAL 1 HOUR) THEN DiskSpace ELSE 0 END), 0) AS HourEventDiskSpace,

    -- Day Stats
    COUNT(CASE WHEN StartDateTime >= (NOW() - INTERVAL 1 DAY) THEN 1 END) AS DayEvents,
    COALESCE(SUM(CASE WHEN StartDateTime >= (NOW() - INTERVAL 1 DAY) THEN DiskSpace ELSE 0 END), 0) AS DayEventDiskSpace,

    -- Week Stats
    COUNT(CASE WHEN StartDateTime >= (NOW() - INTERVAL 7 DAY) THEN 1 END) AS WeekEvents,
    COALESCE(SUM(CASE WHEN StartDateTime >= (NOW() - INTERVAL 7 DAY) THEN DiskSpace ELSE 0 END), 0) AS WeekEventDiskSpace,

    -- Month Stats
    COUNT(CASE WHEN StartDateTime >= (NOW() - INTERVAL 1 MONTH) THEN 1 END) AS MonthEvents,
    COALESCE(SUM(CASE WHEN StartDateTime >= (NOW() - INTERVAL 1 MONTH) THEN DiskSpace ELSE 0 END), 0) AS MonthEventDiskSpace,

    -- Archive Stats
    COUNT(CASE WHEN Archived = 1 THEN 1 END) AS ArchivedEvents,
    COALESCE(SUM(CASE WHEN Archived = 1 THEN DiskSpace ELSE 0 END), 0) AS ArchivedEventDiskSpace,

    -- Totals
    COUNT(Id) AS TotalEvents,
    COALESCE(SUM(DiskSpace), 0) AS TotalEventDiskSpace
FROM Events
GROUP BY MonitorId//

-- Event Summaries snapshot table (SWR pattern)
CREATE TABLE IF NOT EXISTS `Event_Summaries` (
  `MonitorId`              int(10) unsigned NOT NULL,
  `HourEvents`             int(10) DEFAULT 0,
  `HourEventDiskSpace`     bigint DEFAULT 0,
  `DayEvents`              int(10) DEFAULT 0,
  `DayEventDiskSpace`      bigint DEFAULT 0,
  `WeekEvents`             int(10) DEFAULT 0,
  `WeekEventDiskSpace`     bigint DEFAULT 0,
  `MonthEvents`            int(10) DEFAULT 0,
  `MonthEventDiskSpace`    bigint DEFAULT 0,
  `ArchivedEvents`         int(10) DEFAULT 0,
  `ArchivedEventDiskSpace` bigint DEFAULT 0,
  `TotalEvents`            int(10) DEFAULT 0,
  `TotalEventDiskSpace`    bigint DEFAULT 0,
  PRIMARY KEY (`MonitorId`)
) ENGINE=InnoDB//

-- Metadata table for SWR staleness tracking
CREATE TABLE IF NOT EXISTS `Event_Summaries_Metadata` (
  `table_name`   VARCHAR(64) NOT NULL,
  `last_updated` DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00',
  PRIMARY KEY (`table_name`)
) ENGINE=InnoDB//

INSERT IGNORE INTO `Event_Summaries_Metadata`
  (`table_name`, `last_updated`) VALUES ('Event_Summaries', '1970-01-01 00:00:00')//

-- Stored procedure: atomic refresh of Event_Summaries with GET_LOCK to prevent thundering herd
DROP PROCEDURE IF EXISTS `Refresh_Summaries_SWR`//

CREATE PROCEDURE `Refresh_Summaries_SWR`()
proc: BEGIN
  DECLARE v_lock_result INT DEFAULT 0;
  DECLARE v_last DATETIME;

  -- Non-blocking lock: skip if another process is already refreshing
  SET v_lock_result = GET_LOCK('refresh_summaries_lock', 0);
  IF v_lock_result != 1 THEN
    -- Another process holds the lock; return immediately (stale read is fine)
    LEAVE proc;
  END IF;

  -- Double-check staleness inside lock
  SELECT `last_updated` INTO v_last
    FROM `Event_Summaries_Metadata`
    WHERE `table_name` = 'Event_Summaries';

  IF v_last IS NOT NULL AND TIMESTAMPDIFF(SECOND, v_last, NOW()) < 60 THEN
    DO RELEASE_LOCK('refresh_summaries_lock');
    LEAVE proc;
  END IF;

  -- Atomic rename pattern: build new table, swap, drop old
  DROP TABLE IF EXISTS `Event_Summaries_New`;
  CREATE TABLE `Event_Summaries_New` LIKE `Event_Summaries`;
  INSERT INTO `Event_Summaries_New` SELECT * FROM `VIEW_Event_Summaries`;

  DROP TABLE IF EXISTS `Event_Summaries_Old`;
  RENAME TABLE `Event_Summaries` TO `Event_Summaries_Old`,
               `Event_Summaries_New` TO `Event_Summaries`;
  DROP TABLE IF EXISTS `Event_Summaries_Old`;

  -- Update metadata timestamp
  UPDATE `Event_Summaries_Metadata`
    SET `last_updated` = NOW()
    WHERE `table_name` = 'Event_Summaries';

  DO RELEASE_LOCK('refresh_summaries_lock');
END proc//

-- MySQL EVENT for background refresh every 600 seconds (10 minutes)
-- Note: Requires event_scheduler=ON in my.cnf or SET GLOBAL event_scheduler = ON;
DROP EVENT IF EXISTS `Event_Summaries_Refresh_Event`//

CREATE EVENT IF NOT EXISTS `Event_Summaries_Refresh_Event`
  ON SCHEDULE EVERY 600 SECOND
  DO CALL `Refresh_Summaries_SWR`()//

DELIMITER ;
