DROP PROCEDURE IF EXISTS update_storage_stats;

DELIMITER //

CREATE PROCEDURE update_storage_stats(IN StorageId smallint(5), IN space BIGINT)

sql security invoker

deterministic

begin

    update Storage set DiskSpace = COALESCE(DiskSpace,0) + COALESCE(space,0) where Id = StorageId;

end;

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
      ArchivedEvents = ArchivedEvents - 1,
      ArchivedEventDiskSpace = COALESCE(ArchivedEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0),
      TotalEvents = TotalEvents - 1,
      TotalEventDiskSpace = COALESCE(TotalEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0)
      WHERE Id=OLD.MonitorId;
    ELSE
      UPDATE Monitors SET
      TotalEvents = TotalEvents-1,
      TotalEventDiskSpace=COALESCE(TotalEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0)
      WHERE Id=OLD.MonitorId;
    END IF;
  END;

//

UPDATE Storage SET DiskSpace=(SELECT SUM(COALESCE(DiskSpace,0)) FROM Events WHERE StorageId=Storage.Id)//
