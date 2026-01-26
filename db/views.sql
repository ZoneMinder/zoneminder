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

-- Event Summaries View
CREATE OR REPLACE VIEW Event_Summaries AS
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

DELIMITER ;