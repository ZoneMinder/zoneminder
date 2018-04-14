
ALTER TABLE `Monitors` MODIFY `HourEvents` INT(10) DEFAULT NULL;
ALTER TABLE `Monitors` MODIFY `HourEventDiskSpace` BIGINT DEFAULT NULL;
ALTER TABLE `Monitors` MODIFY `DayEvents` INT(10) DEFAULT NULL;
ALTER TABLE `Monitors` MODIFY `DayEventDiskSpace` BIGINT DEFAULT NULL;
ALTER TABLE `Monitors` MODIFY `WeekEvents` INT(10) DEFAULT NULL;
ALTER TABLE `Monitors` MODIFY `WeekEventDiskSpace` BIGINT DEFAULT NULL;
ALTER TABLE `Monitors` MODIFY `MonthEvents` INT(10) DEFAULT NULL;
ALTER TABLE `Monitors` MODIFY `MonthEventDiskSpace` BIGINT DEFAULT NULL;
ALTER TABLE `Monitors` MODIFY `ArchivedEvents` INT(10) DEFAULT NULL;
ALTER TABLE `Monitors` MODIFY `ArchivedEventDiskSpace` BIGINT DEFAULT NULL;
ALTER TABLE `Monitors` MODIFY `TotalEvents` INT(10) DEFAULT NULL;
ALTER TABLE `Monitors` MODIFY `TotalEventDiskSpace` BIGINT DEFAULT NULL;

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

DROP TRIGGER IF EXISTS Events_Hour_update_trigger//

CREATE TRIGGER Events_Hour_update_trigger AFTER UPDATE ON Events_Hour
FOR EACH ROW
  BEGIN
    declare diff BIGINT default 0;

    set diff = COALESCE(NEW.DiskSpace,0) - COALESCE(OLD.DiskSpace,0);
    IF ( diff ) THEN
      IF ( NEW.MonitorID != OLD.MonitorID ) THEN
        UPDATE Monitors SET HourEventDiskSpace=COALESCE(HourEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0) WHERE Monitors.Id=OLD.MonitorId;
        UPDATE Monitors SET HourEventDiskSpace=COALESCE(HourEventDiskSpace,0)-COALESCE(NEW.DiskSpace,0) WHERE Monitors.Id=NEW.MonitorId;
      ELSE
        UPDATE Monitors SET HourEventDiskSpace=COALESCE(HourEventDiskSpace,0)+diff WHERE Monitors.Id=NEW.MonitorId;
      END IF;
    END IF;
  END;
//
DELIMITER ;

delimiter //
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
        UPDATE Monitors SET DayEventDiskSpace=COALESCE(DayEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0) WHERE Monitors.Id=OLD.MonitorId;
        UPDATE Monitors SET DayEventDiskSpace=COALESCE(DayEventDiskSpace,0)+COALESCE(NEW.DiskSpace,0) WHERE Monitors.Id=NEW.MonitorId;
      ELSE
        UPDATE Monitors SET DayEventDiskSpace=COALESCE(DayEventDiskSpace,0)+diff WHERE Monitors.Id=NEW.MonitorId;
      END IF;
    END IF;
  END;
  //


delimiter //
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
        UPDATE Monitors SET WeekEventDiskSpace=COALESCE(WeekEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0) WHERE Monitors.Id=OLD.MonitorId;
        UPDATE Monitors SET WeekEventDiskSpace=COALESCE(WeekEventDiskSpace,0)+COALESCE(NEW.DiskSpace,0) WHERE Monitors.Id=NEW.MonitorId;
      ELSE
        UPDATE Monitors SET WeekEventDiskSpace=COALESCE(WeekEventDiskSpace,0)+diff WHERE Monitors.Id=NEW.MonitorId;
      END IF;
    END IF;
  END;
  //

DELIMITER ;

delimiter //
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
        UPDATE Monitors SET MonthEventDiskSpace=COALESCE(MonthEventDiskSpace,0)-COALESCE(OLD.DiskSpace) WHERE Monitors.Id=OLD.MonitorId;
        UPDATE Monitors SET MonthEventDiskSpace=COALESCE(MonthEventDiskSpace,0)+COALESCE(NEW.DiskSpace) WHERE Monitors.Id=NEW.MonitorId;
      ELSE
        UPDATE Monitors SET MonthEventDiskSpace=COALESCE(MonthEventDiskSpace,0)+diff WHERE Monitors.Id=NEW.MonitorId;
      END IF;
    END IF;
  END;
  //

drop procedure if exists update_storage_stats;
create procedure update_storage_stats(IN StorageId smallint(5), IN space BIGINT)

sql security invoker

deterministic

begin

  update Storage set DiskSpace = COALESCE(DiskSpace,0) + COALESCE(space,0) where Id = StorageId;

end;

//

drop trigger if exists event_update_trigger//

CREATE TRIGGER event_update_trigger AFTER UPDATE ON Events 
FOR EACH ROW
BEGIN
    declare diff BIGINT default 0;

    set diff = COALESCE(NEW.DiskSpace,0) - COALESCE(OLD.DiskSpace,0);
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
      UPDATE Monitors SET ArchivedEvents = COALESCE(ArchivedEvents,0)+1, ArchivedEventDiskSpace = COALESCE(ArchivedEventDiskSpace,0) + COALESCE(NEW.DiskSpace,0) WHERE Id=NEW.MonitorId;
    ELSEIF ( OLD.Archived ) THEN
      DELETE FROM Events_Archived WHERE EventId=OLD.Id;
      UPDATE Monitors SET ArchivedEvents = COALESCE(ArchivedEvents,0)-1, ArchivedEventDiskSpace = COALESCE(ArchivedEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0) WHERE Id=OLD.MonitorId;
    ELSE
      IF ( OLD.DiskSpace != NEW.DiskSpace ) THEN
        UPDATE Events_Archived SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
        UPDATE Monitors SET
          ArchivedEventDiskSpace = COALESCE(ArchivedEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0) + COALESCE(NEW.DiskSpace,0)
          WHERE Id=OLD.MonitorId;
      END IF;
    END IF;
  ELSEIF ( NEW.Archived AND diff ) THEN
    UPDATE Events_Archived SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
  END IF;

  IF ( diff ) THEN
    UPDATE Monitors SET TotalEventDiskSpace = COALESCE(TotalEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0) + COALESCE(NEW.DiskSpace,0) WHERE Id=OLD.MonitorId;
  END IF;

END;

//

delimiter ;

DROP TRIGGER IF EXISTS event_insert_trigger;

delimiter //
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
    call update_storage_stats(OLD.StorageId, -OLD.DiskSpace);
  END IF;
  DELETE FROM Events_Hour WHERE EventId=OLD.Id;
  DELETE FROM Events_Day WHERE EventId=OLD.Id;
  DELETE FROM Events_Week WHERE EventId=OLD.Id;
  DELETE FROM Events_Month WHERE EventId=OLD.Id;
  IF ( OLD.Archived ) THEN
    DELETE FROM Events_Archived WHERE EventId=OLD.Id;
    UPDATE Monitors SET
      ArchivedEvents = COALESCE(ArchivedEvents,1) - 1,
      ArchivedEventDiskSpace = COALESCE(ArchivedEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0),
      TotalEvents = COALESCE(TotalEvents,1) - 1,
      TotalEventDiskSpace = COALESCE(TotalEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0)
      WHERE Id=OLD.MonitorId;
  ELSE
    UPDATE Monitors SET
    TotalEvents = COALESCE(TotalEvents,1)-1,
    TotalEventDiskSpace=COALESCE(TotalEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0)
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
