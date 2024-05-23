/* DateTime is invalid and it being set here will cause warnings because it isn't in the dropdown set of values in Filter edit. */
UPDATE Config SET Value='StartDateTime' WHERE Name='ZM_WEB_EVENT_SORT_FIELD' AND Value='DateTime';

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Events'
     AND column_name = 'StartDateTime'
    ) > 0,
"SELECT 'Column StartDateTime already exists in Events'",
"ALTER TABLE Events CHANGE StartTime StartDateTime datetime default NULL"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Events'
    AND column_name = 'EndDateTime'
    ) > 0,
    "SELECT 'Column EndDateTime already exists in Events'",
    "ALTER TABLE Events CHANGE EndTime EndDateTime datetime default NULL"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Events_Hour'
    AND column_name = 'StartDateTime'
    ) > 0,
    "SELECT 'Column StartDateTime already exists in Events_Hour'",
    "ALTER TABLE Events_Hour CHANGE StartTime StartDateTime datetime default NULL"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Events_Day'
    AND column_name = 'StartDateTime'
    ) > 0,
    "SELECT 'Column StartDateTime already exists in Events_Day'",
    "ALTER TABLE Events_Day CHANGE StartTime StartDateTime datetime default NULL"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Events_Week'
    AND column_name = 'StartDateTime'
    ) > 0,
    "SELECT 'Column StartDateTime already exists in Events_Week'",
    "ALTER TABLE Events_Week CHANGE StartTime StartDateTime datetime default NULL"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Events_Month'
    AND column_name = 'StartDateTime'
    ) > 0,
    "SELECT 'Column StartDateTime already exists in Events_Month'",
    "ALTER TABLE Events_Month CHANGE StartTime StartDateTime datetime default NULL"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

delimiter //

DROP TRIGGER IF EXISTS event_insert_trigger//

/* The assumption is that when an Event is inserted, it has no size yet, so don't bother updating the DiskSpace, just the count.
 * The DiskSpace will get update in the Event Update Trigger
 */
CREATE TRIGGER event_insert_trigger AFTER INSERT ON Events
FOR EACH ROW
  BEGIN

  INSERT INTO Events_Hour (EventId,MonitorId,StartDateTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartDateTime,0);
  INSERT INTO Events_Day (EventId,MonitorId,StartDateTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartDateTime,0);
  INSERT INTO Events_Week (EventId,MonitorId,StartDateTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartDateTime,0);
  INSERT INTO Events_Month (EventId,MonitorId,StartDateTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartDateTime,0);
  UPDATE Monitors SET
  HourEvents = COALESCE(HourEvents,0)+1,
  DayEvents = COALESCE(DayEvents,0)+1,
  WeekEvents = COALESCE(WeekEvents,0)+1,
  MonthEvents = COALESCE(MonthEvents,0)+1,
  TotalEvents = COALESCE(TotalEvents,0)+1
  WHERE Id=NEW.MonitorId;
END;
//

delimiter ;
