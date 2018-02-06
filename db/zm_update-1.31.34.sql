delimiter //

DROP TRIGGER IF EXISTS Events_Hour_delete_trigger//
CREATE TRIGGER Events_Hour_delete_trigger BEFORE DELETE ON Events_Hour
FOR EACH ROW BEGIN
  UPDATE Monitors SET
  HourEvents = COALESCE(HourEvents,1)-1,
  HourEventDiskSpace=COALESCE(HourEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0)
  WHERE Id=OLD.MonitorId;
END;
//

DROP TRIGGER IF EXISTS Events_Day_delete_trigger//
CREATE TRIGGER Events_Day_delete_trigger BEFORE DELETE ON Events_Day
FOR EACH ROW BEGIN
  UPDATE Monitors SET
  DayEvents = COALESCE(DayEvents,1)-1,
  DayEventDiskSpace=COALESCE(DayEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0)
  WHERE Id=OLD.MonitorId;
END;
//

DROP TRIGGER IF EXISTS Events_Week_delete_trigger//
CREATE TRIGGER Events_Week_delete_trigger BEFORE DELETE ON Events_Week
FOR EACH ROW BEGIN
  UPDATE Monitors SET
  WeekEvents = COALESCE(WeekEvents,1)-1,
  WeekEventDiskSpace=COALESCE(WeekEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0)
  WHERE Id=OLD.MonitorId;
END;
//

DROP TRIGGER IF EXISTS Events_Month_delete_trigger//
CREATE TRIGGER Events_Month_delete_trigger BEFORE DELETE ON Events_Month
FOR EACH ROW BEGIN
  UPDATE Monitors SET
  MonthEvents = COALESCE(MonthEvents,1)-1,
  MonthEventDiskSpace=COALESCE(MonthEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0)
  WHERE Id=OLD.MonitorId;
END;
//

DROP TRIGGER IF EXISTS event_insert_trigger//

CREATE TRIGGER event_insert_trigger AFTER INSERT ON Events
FOR EACH ROW
  BEGIN
    INSERT INTO Events_Hour (EventId,MonitorId,StartTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartTime,0);
    INSERT INTO Events_Day (EventId,MonitorId,StartTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartTime,0);
    INSERT INTO Events_Week (EventId,MonitorId,StartTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartTime,0);
    INSERT INTO Events_Month (EventId,MonitorId,StartTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartTime,0);
    UPDATE Monitors SET
    HourEvents = COALESCE(HourEvents,0)+1,
    DayEvents = COALESCE(DayEvents,0)+1,
    WeekEvents = COALESCE(WeekEvents,0)+1,
    MonthEvents = COALESCE(MonthEvents,0)+1,
    TotalEvents = COALESCE(TotalEvents,0)+1
    WHERE Id=NEW.MonitorId;
  END;
  //


