--
-- Index Events for the "overlaps a time window" query.
--
-- Montage review, and any filter with DateTime terms, ask for events
-- overlapping a window: a monitor, an upper bound on StartDateTime, and a lower
-- bound on when the event ended. None of it was indexable - the only MonitorId
-- key was MonitorId alone - so every request read all of that monitor's events
-- and filtered them in memory, no matter how narrow the window.
--
-- Measured on a 7,167 event monitor. Default one hour window: 7,055 rows
-- examined / 25.4ms -> 173 rows / 4ms. Window scrubbed a week back: 6,995 rows
-- / 28ms -> 19 rows / 0.15ms.
--
-- Two range columns cannot both narrow one B-tree, and these two are mirror
-- images - EndDateTime >= T1 is open-ended upwards so it is cheap near now and
-- degenerate for an old window, StartDateTime <= T2 is the reverse - so both
-- keys are added and the optimiser picks per query.
--
-- Replaces two existing keys:
--   Events_MonitorId_idx (MonitorId) is a leftmost prefix of the new
--     (MonitorId, StartDateTime), so it is redundant.
--   Events_EndDateTime_DiskSpace (EndDateTime, DiskSpace) existed for the scan
--     that hunted events with no DiskSpace set. DiskSpace is now set when the
--     event is finalised in C++, so nothing scans for it. The EndDateTime half
--     is still needed - zmaudit looks for events that were never closed, with
--     no monitor to scope it - which is why EndDateTime leads the new key.
--
-- The replacements are added before the old keys are dropped so there is never
-- a point with neither. Idempotent, so re-running is a no-op.
--

-- (MonitorId, StartDateTime): MonitorId leads because it is the equality;
-- once a range column is reached later columns cannot narrow the scan.
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE()
     AND table_name = 'Events' AND index_name = 'Events_MonitorId_StartDateTime_idx') > 0,
"SELECT 'Events_MonitorId_StartDateTime_idx already exists on Events'",
"ALTER TABLE `Events` ADD INDEX `Events_MonitorId_StartDateTime_idx` (`MonitorId`,`StartDateTime`)"
));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- (EndDateTime, MonitorId): EndDateTime leads so this also covers zmaudit's
-- EndDateTime IS NULL scan, which has no monitor to scope it.
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE()
     AND table_name = 'Events' AND index_name = 'Events_EndDateTime_MonitorId_idx') > 0,
"SELECT 'Events_EndDateTime_MonitorId_idx already exists on Events'",
"ALTER TABLE `Events` ADD INDEX `Events_EndDateTime_MonitorId_idx` (`EndDateTime`,`MonitorId`)"
));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Now superseded by the leftmost prefix of Events_MonitorId_StartDateTime_idx.
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE()
     AND table_name = 'Events' AND index_name = 'Events_MonitorId_idx') > 0,
"ALTER TABLE `Events` DROP INDEX `Events_MonitorId_idx`",
"SELECT 'Events_MonitorId_idx already removed from Events'"
));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- The (EndDateTime, DiskSpace) key, under both names it has been created with.
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE()
     AND table_name = 'Events' AND index_name = 'Events_EndDateTime_DiskSpace') > 0,
"ALTER TABLE `Events` DROP INDEX `Events_EndDateTime_DiskSpace`",
"SELECT 'Events_EndDateTime_DiskSpace already removed from Events'"
));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE()
     AND table_name = 'Events' AND index_name = 'Events_EndTime_DiskSpace') > 0,
"ALTER TABLE `Events` DROP INDEX `Events_EndTime_DiskSpace`",
"SELECT 'Events_EndTime_DiskSpace already removed from Events'"
));
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
