
delimiter //
DROP TRIGGER IF EXISTS Events_Hour_delete_trigger//
CREATE TRIGGER Events_Hour_delete_trigger BEFORE DELETE ON Events_Hour
FOR EACH ROW BEGIN
  UPDATE Monitors SET
  HourEvents = COALESCE(HourEvents,1)-1,
  HourEventDiskSpace=GREATEST(COALESCE(HourEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
  WHERE Id=OLD.MonitorId;
END; 
//

DROP TRIGGER IF EXISTS Events_Hour_update_trigger//

CREATE TRIGGER Events_Hour_update_trigger AFTER UPDATE ON Events_Hour
FOR EACH ROW
  BEGIN
    declare diff BIGINT default 0;

    set diff = COALESCE(NEW.DiskSpace,0) - COALESCE(OLD.DiskSpace,0);
    IF ( diff ) THEN
      IF ( NEW.MonitorID != OLD.MonitorID ) THEN
        UPDATE Monitors SET HourEventDiskSpace=GREATEST(COALESCE(HourEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0) WHERE Monitors.Id=OLD.MonitorId;
        UPDATE Monitors SET HourEventDiskSpace=COALESCE(HourEventDiskSpace,0)+COALESCE(NEW.DiskSpace,0) WHERE Monitors.Id=NEW.MonitorId;
      ELSE
        UPDATE Monitors SET HourEventDiskSpace=COALESCE(HourEventDiskSpace,0)+diff WHERE Monitors.Id=NEW.MonitorId;
      END IF;
    END IF;
  END;
//

DROP TRIGGER IF EXISTS Events_Day_delete_trigger//
CREATE TRIGGER Events_Day_delete_trigger BEFORE DELETE ON Events_Day
FOR EACH ROW BEGIN
  UPDATE Monitors SET
  DayEvents = GREATEST(COALESCE(DayEvents,1)-1,0),
  DayEventDiskSpace=GREATEST(COALESCE(DayEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
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
        UPDATE Monitors SET DayEventDiskSpace=GREATEST(COALESCE(DayEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0) WHERE Monitors.Id=OLD.MonitorId;
        UPDATE Monitors SET DayEventDiskSpace=COALESCE(DayEventDiskSpace,0)+COALESCE(NEW.DiskSpace,0) WHERE Monitors.Id=NEW.MonitorId;
      ELSE
        UPDATE Monitors SET DayEventDiskSpace=GREATEST(COALESCE(DayEventDiskSpace,0)+diff,0) WHERE Monitors.Id=NEW.MonitorId;
      END IF;
    END IF;
  END;
  //


DROP TRIGGER IF EXISTS Events_Week_delete_trigger//
CREATE TRIGGER Events_Week_delete_trigger BEFORE DELETE ON Events_Week
FOR EACH ROW BEGIN
  UPDATE Monitors SET
  WeekEvents = COALESCE(WeekEvents,1)-1,
  WeekEventDiskSpace=GREATEST(COALESCE(WeekEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
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
        UPDATE Monitors SET WeekEventDiskSpace=GREATEST(COALESCE(WeekEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0) WHERE Monitors.Id=OLD.MonitorId;
        UPDATE Monitors SET WeekEventDiskSpace=COALESCE(WeekEventDiskSpace,0)+COALESCE(NEW.DiskSpace,0) WHERE Monitors.Id=NEW.MonitorId;
      ELSE
        UPDATE Monitors SET WeekEventDiskSpace=GREATEST(COALESCE(WeekEventDiskSpace,0)+diff,0) WHERE Monitors.Id=NEW.MonitorId;
      END IF;
    END IF;
  END;
  //

DROP TRIGGER IF EXISTS Events_Month_delete_trigger//
CREATE TRIGGER Events_Month_delete_trigger BEFORE DELETE ON Events_Month
FOR EACH ROW BEGIN
  UPDATE Monitors SET
  MonthEvents = COALESCE(MonthEvents,1)-1,
  MonthEventDiskSpace=GREATEST(COALESCE(MonthEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
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
        UPDATE Monitors SET MonthEventDiskSpace=GREATEST(COALESCE(MonthEventDiskSpace,0)-COALESCE(OLD.DiskSpace),0) WHERE Monitors.Id=OLD.MonitorId;
        UPDATE Monitors SET MonthEventDiskSpace=COALESCE(MonthEventDiskSpace,0)+COALESCE(NEW.DiskSpace) WHERE Monitors.Id=NEW.MonitorId;
      ELSE
        UPDATE Monitors SET MonthEventDiskSpace=GREATEST(COALESCE(MonthEventDiskSpace,0)+diff,0) WHERE Monitors.Id=NEW.MonitorId;
      END IF;
    END IF;
  END;
  //

drop procedure if exists update_storage_stats//

drop trigger if exists event_update_trigger//

CREATE TRIGGER event_update_trigger AFTER UPDATE ON Events 
FOR EACH ROW
BEGIN
  declare diff BIGINT default 0;

  set diff = COALESCE(NEW.DiskSpace,0) - COALESCE(OLD.DiskSpace,0);
  IF ( NEW.StorageId = OLD.StorageID ) THEN
    IF ( diff ) THEN
      UPDATE Storage SET DiskSpace = GREATEST(COALESCE(DiskSpace,0) + diff,0) WHERE Id = OLD.StorageId;
    END IF;
  ELSE
    IF ( NEW.DiskSpace ) THEN
      UPDATE Storage SET DiskSpace = COALESCE(DiskSpace,0) + NEW.DiskSpace WHERE Id = NEW.StorageId;
    END IF;
    IF ( OLD.DiskSpace ) THEN
      UPDATE Storage SET DiskSpace = GREATEST(COALESCE(DiskSpace,0) - OLD.DiskSpace,0) WHERE Id = OLD.StorageId;
    END IF;
  END IF;

  UPDATE Events_Hour SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
  UPDATE Events_Day SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
  UPDATE Events_Week SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
  UPDATE Events_Month SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;

  IF ( NEW.Archived != OLD.Archived ) THEN
    IF ( NEW.Archived ) THEN
      INSERT INTO Events_Archived (EventId,MonitorId,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.DiskSpace);
      UPDATE Monitors SET ArchivedEvents = COALESCE(ArchivedEvents,0)+1, ArchivedEventDiskSpace = COALESCE(ArchivedEventDiskSpace,0) + COALESCE(NEW.DiskSpace,0) WHERE Id=NEW.MonitorId;
    ELSEIF ( OLD.Archived ) THEN
      DELETE FROM Events_Archived WHERE EventId=OLD.Id;
      UPDATE Monitors
        SET
          ArchivedEvents = GREATEST(COALESCE(ArchivedEvents,0)-1,0),
          ArchivedEventDiskSpace = GREATEST(COALESCE(ArchivedEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0),0)
        WHERE Id=OLD.MonitorId;
    ELSE
      IF ( OLD.DiskSpace != NEW.DiskSpace ) THEN
        UPDATE Events_Archived SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
        UPDATE Monitors SET
          ArchivedEventDiskSpace = GREATEST(COALESCE(ArchivedEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0) + COALESCE(NEW.DiskSpace,0),0)
          WHERE Id=OLD.MonitorId;
      END IF;
    END IF;
  ELSEIF ( NEW.Archived AND diff ) THEN
    UPDATE Events_Archived SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
  END IF;

  IF ( diff ) THEN
    UPDATE Monitors
      SET
        TotalEventDiskSpace = GREATEST(COALESCE(TotalEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0) + COALESCE(NEW.DiskSpace,0),0)
      WHERE Id=OLD.MonitorId;
  END IF;

END;

//

DROP TRIGGER IF EXISTS event_insert_trigger//

/* The assumption is that when an Event is inserted, it has no size yet, so don't bother updating the DiskSpace, just the count.
 * The DiskSpace will get update in the Event Update Trigger
 */
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

DROP TRIGGER IF EXISTS event_delete_trigger//

CREATE TRIGGER event_delete_trigger BEFORE DELETE ON Events
FOR EACH ROW
BEGIN
  IF ( OLD.DiskSpace ) THEN
    UPDATE Storage SET DiskSpace = GREATEST(COALESCE(DiskSpace,0) - COALESCE(OLD.DiskSpace,0),0) WHERE Id = OLD.StorageId;
  END IF;
  DELETE FROM Events_Hour WHERE EventId=OLD.Id;
  DELETE FROM Events_Day WHERE EventId=OLD.Id;
  DELETE FROM Events_Week WHERE EventId=OLD.Id;
  DELETE FROM Events_Month WHERE EventId=OLD.Id;
  IF ( OLD.Archived ) THEN
    DELETE FROM Events_Archived WHERE EventId=OLD.Id;
    UPDATE Monitors SET
      ArchivedEvents = GREATEST(COALESCE(ArchivedEvents,1) - 1,0),
      ArchivedEventDiskSpace = GREATEST(COALESCE(ArchivedEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0),0),
      TotalEvents = GREATEST(COALESCE(TotalEvents,1) - 1,0),
      TotalEventDiskSpace = GREATEST(COALESCE(TotalEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0),0)
      WHERE Id=OLD.MonitorId;
  ELSE
    UPDATE Monitors SET
    TotalEvents = GREATEST(COALESCE(TotalEvents,1)-1,0),
    TotalEventDiskSpace=GREATEST(COALESCE(TotalEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
    WHERE Id=OLD.MonitorId;
  END IF;
END;

//

DROP TRIGGER IF EXISTS Zone_Insert_Trigger//
CREATE TRIGGER Zone_Insert_Trigger AFTER INSERT ON Zones
FOR EACH ROW
  BEGIN
    UPDATE Monitors SET ZoneCount=(SELECT COUNT(*) FROM Zones WHERE MonitorId=NEW.MonitorId) WHERE Id=NEW.MonitorID;
  END
//
DROP TRIGGER IF EXISTS Zone_Delete_Trigger//
CREATE TRIGGER Zone_Delete_Trigger AFTER DELETE ON Zones
FOR EACH ROW
  BEGIN
    UPDATE Monitors SET ZoneCount=(SELECT COUNT(*) FROM Zones WHERE MonitorId=OLD.MonitorId) WHERE Id=OLD.MonitorID;
  END
//

DELIMITER ;

REPLACE INTO Events_Hour SELECT Id,MonitorId,StartTime,DiskSpace FROM Events WHERE StartTime > DATE_SUB(NOW(),  INTERVAL 1 hour);
REPLACE INTO Events_Day SELECT Id,MonitorId,StartTime,DiskSpace FROM Events WHERE StartTime > DATE_SUB(NOW(),  INTERVAL 1 day);
REPLACE INTO Events_Week SELECT Id,MonitorId,StartTime,DiskSpace FROM Events WHERE StartTime > DATE_SUB(NOW(),  INTERVAL 1 week);
REPLACE INTO Events_Month SELECT Id,MonitorId,StartTime,DiskSpace FROM Events WHERE StartTime > DATE_SUB(NOW(),  INTERVAL 1 month);
REPLACE INTO Events_Archived SELECT Id,MonitorId,DiskSpace FROM Events WHERE Archived=1;

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

