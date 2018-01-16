delimiter //
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

UPDATE Monitors SET ZoneCount=(SELECT COUNT(Id) FROM Zones WHERE MonitorId=Monitors.Id)//

drop trigger if exists event_update_trigger//

CREATE TRIGGER event_update_trigger AFTER UPDATE ON Events 
FOR EACH ROW
BEGIN
    declare diff BIGINT default 0;

    set diff = NEW.DiskSpace - OLD.DiskSpace;
  IF ( NEW.StorageId = OLD.StorageID ) THEN
    IF ( diff ) THEN
      call update_storage_stats(OLD.StorageId, diff);
    END IF;
  ELSE
    IF ( NEW.DiskSpace ) THEN
      call update_storage_stats(NEW.StorageId, NEW.DiskSpace);
    END IF;
    IF ( OLD.DiskSpace ) THEN
      call update_storage_stats(OLD.StorageId, -OLD.DiskSpace);
    END IF;
  END IF;

  UPDATE Events_Hour SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
  UPDATE Events_Day SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
  UPDATE Events_Week SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
  UPDATE Events_Month SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
  IF ( NEW.Archived != OLD.Archived ) THEN
    IF ( NEW.Archived ) THEN
      INSERT INTO Events_Archived (EventId,MonitorId,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.DiskSpace);
      UPDATE Monitors SET ArchivedEvents = ArchivedEvents+1, ArchivedEventDiskSpace = ArchivedEventDiskSpace + NEW.DiskSpace WHERE Id=NEW.MonitorId;
    ELSEIF ( OLD.Archived ) THEN
      DELETE FROM Events_Archived WHERE EventId=OLD.Id;
      UPDATE Monitors SET ArchivedEvents =ArchivedEvents-1, ArchivedEventDiskSpace = ArchivedEventDiskSpace - OLD.DiskSpace WHERE Id=OLD.MonitorId;
    ELSE
      IF ( OLD.DiskSpace != NEW.DiskSpace ) THEN
        UPDATE Events_Archived SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
        UPDATE Monitors SET
          ArchivedEventDiskSpace = ArchivedEventDiskSpace - OLD.DiskSpace + NEW.DiskSpace
          WHERE Id=OLD.MonitorId;
      END IF;
    END IF;
  END IF;

  IF ( OLD.DiskSpace != NEW.DiskSpace ) THEN
    UPDATE Monitors SET TotalEventDiskSpace = TotalEventDiskSpace - OLD.DiskSpace + NEW.DiskSpace WHERE Id=OLD.MonitorId;
  END IF;

END;

//

delimiter ;

DROP TRIGGER IF EXISTS event_insert_trigger;

delimiter //
create trigger event_insert_trigger after insert on Events
for each row
  begin

  INSERT INTO Events_Hour (EventId,MonitorId,StartTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartTime,0);
  INSERT INTO Events_Day (EventId,MonitorId,StartTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartTime,0);
  INSERT INTO Events_Week (EventId,MonitorId,StartTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartTime,0);
  INSERT INTO Events_Month (EventId,MonitorId,StartTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartTime,0);
end;
//

delimiter ;


drop trigger if exists event_delete_trigger;

delimiter //

CREATE TRIGGER event_delete_trigger BEFORE DELETe on Events

FOR EACH ROW
BEGIN
  call update_storage_stats(OLD.StorageId, -OLD.DiskSpace);
  DELETE FROM Events_Hour WHERE EventId=OLD.Id;
  DELETE FROM Events_Day WHERE EventId=OLD.Id;
  DELETE FROM Events_Week WHERE EventId=OLD.Id;
  DELETE FROM Events_Month WHERE EventId=OLD.Id;
  IF ( OLD.Archived ) THEN
    DELETE FROM Events_Archived WHERE EventId=OLD.Id;
    UPDATE Monitors SET
      ArchivedEvents = ArchivedEvents - 1,
      ArchivedEventDiskSpace = ArchivedEventDiskSpace - OLD.DiskSpace,
      TotalEvents = TotalEvents - 1,
      TotalEventDiskSpace = TotalEventDiskSpace - OLD.DiskSpace
      WHERE Id=OLD.MonitorId;
  ELSE
    UPDATE Monitors SET
    TotalEvents = TotalEvents-1,
    TotalEventDiskSpace=TotalEventDiskSpace-OLD.DiskSpace
    WHERE Id=OLD.MonitorId;
  END IF;
END;

//

delimiter ;

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

