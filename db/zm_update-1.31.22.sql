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
UPDATE Storage SET DiskSpace=(SELECT SUM(DiskSpace) FROM Events WHERE StorageId=Storage.Id);
