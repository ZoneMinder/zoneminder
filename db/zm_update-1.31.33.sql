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

