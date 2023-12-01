
delimiter //
DROP TRIGGER IF EXISTS Events_Hour_delete_trigger//
DROP TRIGGER IF EXISTS Events_Hour_update_trigger//
DROP TRIGGER IF EXISTS Events_Day_delete_trigger//
DROP TRIGGER IF EXISTS Events_Day_update_trigger//
DROP TRIGGER IF EXISTS Events_Week_delete_trigger//
DROP TRIGGER IF EXISTS Events_Week_update_trigger//
DROP TRIGGER IF EXISTS Events_Month_delete_trigger//
DROP TRIGGER IF EXISTS Events_Month_update_trigger//
DROP TRIGGER IF EXISTS event_insert_trigger//
DROP TRIGGER IF EXISTS event_update_trigger//
DROP TRIGGER IF EXISTS event_delete_trigger//

drop procedure if exists update_storage_stats//

DROP TRIGGER IF EXISTS Zone_Insert_Trigger//
CREATE TRIGGER Zone_Insert_Trigger AFTER INSERT ON Zones
FOR EACH ROW
  BEGIN
    UPDATE Monitors SET ZoneCount=(SELECT COUNT(*) FROM Zones WHERE MonitorId=NEW.MonitorId) WHERE Monitors.Id=NEW.MonitorID;
  END
//
DROP TRIGGER IF EXISTS Zone_Delete_Trigger//
CREATE TRIGGER Zone_Delete_Trigger AFTER DELETE ON Zones
FOR EACH ROW
  BEGIN
    UPDATE Monitors SET ZoneCount=(SELECT COUNT(*) FROM Zones WHERE MonitorId=OLD.MonitorId) WHERE Monitors.Id=OLD.MonitorID;
  END
//

DELIMITER ;
