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

DROP TRIGGER IF EXISTS Events_Hour_update_trigger;
CREATE TRIGGER Events_Hour_update_trigger AFTER UPDATE ON Events_Hour
FOR EACH ROW
  BEGIN
    declare diff BIGINT default 0;

    set diff = COALESCE(NEW.DiskSpace,0) - COALESCE(OLD.DiskSpace,0);
    IF ( diff ) THEN
      UPDATE Monitors SET HourEventDiskSpace=COALESCE(HourEventDiskSpace,0)+diff WHERE Monitors.Id=MonitorId;
    END IF;
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

DROP TRIGGER IF EXISTS Events_Day_update_trigger;
CREATE TRIGGER Events_Day_update_trigger AFTER UPDATE ON Events_Day
FOR EACH ROW
  BEGIN
    declare diff BIGINT default 0;

    set diff = COALESCE(NEW.DiskSpace,0) - COALESCE(OLD.DiskSpace,0);
    IF ( diff ) THEN
      UPDATE Monitors SET DayEventDiskSpace=COALESCE(DayEventDiskSpace,0)+diff WHERE Monitors.Id=MonitorId;
    END IF;
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

DROP TRIGGER IF EXISTS Events_Week_update_trigger;
CREATE TRIGGER Events_Week_update_trigger AFTER UPDATE ON Events_Week
FOR EACH ROW
  BEGIN
    declare diff BIGINT default 0;

    set diff = COALESCE(NEW.DiskSpace,0) - COALESCE(OLD.DiskSpace,0);
    IF ( diff ) THEN
      UPDATE Monitors SET WeekEventDiskSpace=COALESCE(WeekEventDiskSpace,0)+diff WHERE Monitors.Id=MonitorId;
    END IF;
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

DROP TRIGGER IF EXISTS Events_Month_update_trigger;
CREATE TRIGGER Events_Month_update_trigger AFTER UPDATE ON Events_Month
FOR EACH ROW
  BEGIN
    declare diff BIGINT default 0;

    set diff = COALESCE(NEW.DiskSpace,0) - COALESCE(OLD.DiskSpace,0);
    IF ( diff ) THEN
      UPDATE Monitors SET MonthEventDiskSpace=COALESCE(MonthEventDiskSpace,0)+diff WHERE Monitors.Id=MonitorId;
    END IF;
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

delimiter ;
SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
    AND table_name = 'Events'
    AND column_name = 'Scheme'
  ) > 0,
  "SELECT 'Column Scheme already exists in Events'",
  "ALTER TABLE Events ADD `Scheme`   enum('Deep','Medium','Shallow') NOT NULL default 'Deep' AFTER `DiskSpace`"
  ));

  PREPARE stmt FROM @s;
  EXECUTE stmt;


UPDATE Monitors SET
TotalEvents=(SELECT COUNT(Id) FROM Events WHERE MonitorId=Monitors.Id),
TotalEventDiskSpace=(SELECT SUM(DiskSpace) FROM Events WHERE MonitorId=Monitors.Id AND DiskSpace IS NOT NULL),
HourEvents=(SELECT COUNT(Id) FROM Events WHERE MonitorId=Monitors.Id AND StartTime > DATE_SUB( NOW(), INTERVAL 1 hour) ),
HourEventDiskSpace=(SELECT SUM(DiskSpace) FROM Events WHERE MonitorId=Monitors.Id AND StartTime > DATE_SUB(NOW(), INTERVAL 1 hour) AND DiskSpace IS NOT NULL),
DayEvents=(SELECT COUNT(Id) FROM Events WHERE MonitorId=Monitors.Id AND StartTime > DATE_SUB(NOW(), INTERVAL 1 day)),
DayEventDiskSpace=(SELECT SUM(DiskSpace) FROM Events WHERE MonitorId=Monitors.Id AND StartTime > DATE_SUB(NOW(), INTERVAL 1 day) AND DiskSpace IS NOT NULL),
WeekEvents=(SELECT COUNT(Id) FROM Events WHERE MonitorId=Monitors.Id AND StartTime > DATE_SUB(NOW(), INTERVAL 1 week)),
WeekEventDiskSpace=(SELECT SUM(DiskSpace) FROM Events WHERE MonitorId=Monitors.Id AND StartTime > DATE_SUB(NOW(), INTERVAL 1 week) AND DiskSpace IS NOT NULL),
MonthEvents=(SELECT COUNT(Id) FROM Events WHERE MonitorId=Monitors.Id AND StartTime > DATE_SUB( NOW(), INTERVAL 1 month)),
MonthEventDiskSpace=(SELECT SUM(DiskSpace) FROM Events WHERE MonitorId=Monitors.Id AND StartTime > DATE_SUB(NOW(), INTERVAL 1 month) AND DiskSpace IS NOT NULL),
ArchivedEvents=(SELECT COUNT(Id) FROM Events WHERE MonitorId=Monitors.Id AND Archived=1),
ArchivedEventDiskSpace=(SELECT SUM(DiskSpace) FROM Events WHERE MonitorId=Monitors.Id AND Archived=1 AND DiskSpace IS NOT NULL)
