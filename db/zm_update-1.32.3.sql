--
-- This updates a 1.32.2 database to 1.32.3
--

--
-- Update our triggers to sum event counts properly
--

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
      IF ( NEW.MonitorID != OLD.MonitorID ) THEN
        UPDATE Monitors SET HourEventDiskSpace=COALESCE(DayEventDiskSpace,0)-OLD.DiskSpace WHERE Monitors.Id=OLD.MonitorId;
        UPDATE Monitors SET HourEventDiskSpace=COALESCE(DayEventDiskSpace,0)+NEW.DiskSpace WHERE Monitors.Id=NEW.MonitorId;
      ELSE 
        UPDATE Monitors SET HourEventDiskSpace=COALESCE(DayEventDiskSpace,0)+diff WHERE Monitors.Id=NEW.MonitorId;
    END IF;
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
      IF ( NEW.MonitorID != OLD.MonitorID ) THEN
        UPDATE Monitors SET DayEventDiskSpace=COALESCE(DayEventDiskSpace,0)-OLD.DiskSpace WHERE Monitors.Id=OLD.MonitorId;
        UPDATE Monitors SET DayEventDiskSpace=COALESCE(DayEventDiskSpace,0)+NEW.DiskSpace WHERE Monitors.Id=NEW.MonitorId;
      ELSE 
        UPDATE Monitors SET DayEventDiskSpace=COALESCE(DayEventDiskSpace,0)+diff WHERE Monitors.Id=NEW.MonitorId;
      END IF;
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
      IF ( NEW.MonitorID != OLD.MonitorID ) THEN
        UPDATE Monitors SET WeekEventDiskSpace=COALESCE(WeekEventDiskSpace,0)-OLD.DiskSpace WHERE Monitors.Id=OLD.MonitorId;
        UPDATE Monitors SET WeekEventDiskSpace=COALESCE(WeekEventDiskSpace,0)+NEW.DiskSpace WHERE Monitors.Id=NEW.MonitorId;
      ELSE 
        UPDATE Monitors SET WeekEventDiskSpace=COALESCE(WeekEventDiskSpace,0)+diff WHERE Monitors.Id=NEW.MonitorId;
      END IF;
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
      IF ( NEW.MonitorID != OLD.MonitorID ) THEN
        UPDATE Monitors SET MonthEventDiskSpace=COALESCE(MonthEventDiskSpace,0)-OLD.DiskSpace WHERE Monitors.Id=OLD.MonitorId;
        UPDATE Monitors SET MonthEventDiskSpace=COALESCE(MonthEventDiskSpace,0)+NEW.DiskSpace WHERE Monitors.Id=NEW.MonitorId;
      ELSE 
        UPDATE Monitors SET MonthEventDiskSpace=COALESCE(MonthEventDiskSpace,0)+diff WHERE Monitors.Id=NEW.MonitorId;
      END IF;
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
ArchivedEventDiskSpace=(SELECT SUM(DiskSpace) FROM Events WHERE MonitorId=Monitors.Id AND Archived=1 AND DiskSpace IS NOT NULL);

UPDATE Monitors INNER JOIN (
  SELECT  MonitorId,
    COUNT(Id) AS TotalEvents,
    SUM(DiskSpace) AS TotalEventDiskSpace,
    SUM(IF(Archived,1,0)) AS ArchivedEvents,
    SUM(IF(Archived,DiskSpace,0)) AS ArchivedEventDiskSpace,
    SUM(IF(StartTime > DATE_SUB(NOW(), INTERVAL 1 hour),1,0)) AS HourEvents,
    SUM(IF(StartTime > DATE_SUB(NOW(), INTERVAL 1 hour),DiskSpace,0)) AS HourEventDiskSpace,
    SUM(IF(StartTime > DATE_SUB(NOW(), INTERVAL 1 day),1,0)) AS DayEvents,
    SUM(IF(StartTime > DATE_SUB(NOW(), INTERVAL 1 day),DiskSpace,0)) AS DayEventDiskSpace,
    SUM(IF(StartTime > DATE_SUB(NOW(), INTERVAL 1 week),1,0)) AS WeekEvents,
    SUM(IF(StartTime > DATE_SUB(NOW(), INTERVAL 1 week),DiskSpace,0)) AS WeekEventDiskSpace,
    SUM(IF(StartTime > DATE_SUB(NOW(), INTERVAL 1 month),1,0)) AS MonthEvents,
    SUM(IF(StartTime > DATE_SUB(NOW(), INTERVAL 1 month),DiskSpace,0)) AS MonthEventDiskSpace
    FROM Events GROUP BY MonitorId
    ) AS E ON E.MonitorId=Monitors.Id SET
    Monitors.TotalEvents = E.TotalEvents,
    Monitors.TotalEventDiskSpace = E.TotalEventDiskSpace,
    Monitors.ArchivedEvents = E.ArchivedEvents,
    Monitors.ArchivedEventDiskSpace = E.ArchivedEventDiskSpace,
    Monitors.HourEvents = E.HourEvents,
    Monitors.HourEventDiskSpace = E.HourEventDiskSpace,
    Monitors.DayEvents = E.DayEvents,
    Monitors.DayEventDiskSpace = E.DayEventDiskSpace,
    Monitors.WeekEvents = E.WeekEvents,
    Monitors.WeekEventDiskSpace = E.WeekEventDiskSpace,
    Monitors.MonthEvents = E.MonthEvents,
    Monitors.MonthEventDiskSpace = E.MonthEventDiskSpace;

