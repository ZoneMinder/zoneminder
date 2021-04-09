SELECT 'This update may make changes that require SUPER privileges. If you see an error message saying:

ERROR 1419 (HY000) at line 298: You do not have the SUPER privilege and binary logging is enabled (you *might* want to use the less safe log_bin_trust_function_creators variable)

You will have to either run this update as root manually using something like (on ubuntu/debian)

sudo mysql --defaults-file=/etc/mysql/debian.cnf zm < /usr/share/zoneminder/db/zm_update-1.35.24.sql

OR

sudo mysql --defaults-file=/etc/mysql/debian.cnf "set global log_bin_trust_function_creators=1;"
sudo zmupdate.pl

OR

Turn off binary logging in your mysql server by adding this to your mysql config.
[mysqld]
skip-log-bin

';

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Event_Summaries'
    ) > 0
    ,
    "SELECT 'Event_Summaries Already exists'",
    "
    CREATE TABLE `Event_Summaries` (
  `MonitorId` int(10) unsigned NOT NULL,
  `TotalEvents` int(10) default NULL,
  `TotalEventDiskSpace` bigint default NULL,
  `HourEvents` int(10) default NULL,
  `HourEventDiskSpace` bigint default NULL,
  `DayEvents` int(10) default NULL,
  `DayEventDiskSpace` bigint default NULL,
  `WeekEvents` int(10) default NULL,
  `WeekEventDiskSpace` bigint default NULL,
  `MonthEvents` int(10) default NULL,
  `MonthEventDiskSpace` bigint default NULL,
  `ArchivedEvents` int(10) default NULL,
  `ArchivedEventDiskSpace` bigint default NULL,
  PRIMARY KEY (`MonitorId`)
) ENGINE=InnoDb;
"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

UPDATE Event_Summaries INNER JOIN (
  SELECT  MonitorId,
    COUNT(Id) AS TotalEvents,
    SUM(DiskSpace) AS TotalEventDiskSpace,
    SUM(IF(Archived,1,0)) AS ArchivedEvents,
    SUM(IF(Archived,DiskSpace,0)) AS ArchivedEventDiskSpace,
    SUM(IF(StartDateTime > DATE_SUB(NOW(), INTERVAL 1 hour),1,0)) AS HourEvents,
    SUM(IF(StartDateTime > DATE_SUB(NOW(), INTERVAL 1 hour),DiskSpace,0)) AS HourEventDiskSpace,
    SUM(IF(StartDateTime > DATE_SUB(NOW(), INTERVAL 1 day),1,0)) AS DayEvents,
    SUM(IF(StartDateTime > DATE_SUB(NOW(), INTERVAL 1 day),DiskSpace,0)) AS DayEventDiskSpace,
    SUM(IF(StartDateTime > DATE_SUB(NOW(), INTERVAL 1 week),1,0)) AS WeekEvents,
    SUM(IF(StartDateTime > DATE_SUB(NOW(), INTERVAL 1 week),DiskSpace,0)) AS WeekEventDiskSpace,
    SUM(IF(StartDateTime > DATE_SUB(NOW(), INTERVAL 1 month),1,0)) AS MonthEvents,
    SUM(IF(StartDateTime > DATE_SUB(NOW(), INTERVAL 1 month),DiskSpace,0)) AS MonthEventDiskSpace
    FROM Events GROUP BY MonitorId
    ) AS E ON E.MonitorId=Event_Summaries.MonitorId SET
    Event_Summaries.TotalEvents = E.TotalEvents,
    Event_Summaries.TotalEventDiskSpace = E.TotalEventDiskSpace,
    Event_Summaries.ArchivedEvents = E.ArchivedEvents,
    Event_Summaries.ArchivedEventDiskSpace = E.ArchivedEventDiskSpace,
    Event_Summaries.HourEvents = E.HourEvents,
    Event_Summaries.HourEventDiskSpace = E.HourEventDiskSpace,
    Event_Summaries.DayEvents = E.DayEvents,
    Event_Summaries.DayEventDiskSpace = E.DayEventDiskSpace,
    Event_Summaries.WeekEvents = E.WeekEvents,
    Event_Summaries.WeekEventDiskSpace = E.WeekEventDiskSpace,
    Event_Summaries.MonthEvents = E.MonthEvents,
    Event_Summaries.MonthEventDiskSpace = E.MonthEventDiskSpace;

delimiter //
DROP TRIGGER IF EXISTS Events_Hour_delete_trigger//
CREATE TRIGGER Events_Hour_delete_trigger BEFORE DELETE ON Events_Hour
FOR EACH ROW BEGIN
  UPDATE Event_Summaries SET
  HourEvents = GREATEST(COALESCE(HourEvents,1)-1,0),
  HourEventDiskSpace=GREATEST(COALESCE(HourEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
  WHERE Event_Summaries.MonitorId=OLD.MonitorId;
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
        UPDATE Event_Summaries SET HourEventDiskSpace=GREATEST(COALESCE(HourEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0) WHERE Event_Summaries.MonitorId=OLD.MonitorId;
        UPDATE Event_Summaries SET HourEventDiskSpace=COALESCE(HourEventDiskSpace,0)+COALESCE(NEW.DiskSpace,0) WHERE Event_Summaries.MonitorId=NEW.MonitorId;
      ELSE
        UPDATE Event_Summaries SET HourEventDiskSpace=COALESCE(HourEventDiskSpace,0)+diff WHERE Event_Summaries.MonitorId=NEW.MonitorId;
      END IF;
    END IF;
  END;
//

DROP TRIGGER IF EXISTS Events_Day_delete_trigger//
CREATE TRIGGER Events_Day_delete_trigger BEFORE DELETE ON Events_Day
FOR EACH ROW BEGIN
  UPDATE Event_Summaries SET
  DayEvents = GREATEST(COALESCE(DayEvents,1)-1,0),
  DayEventDiskSpace=GREATEST(COALESCE(DayEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
  WHERE Event_Summaries.MonitorId=OLD.MonitorId;
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
        UPDATE Event_Summaries SET DayEventDiskSpace=GREATEST(COALESCE(DayEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0) WHERE Event_Summaries.MonitorId=OLD.MonitorId;
        UPDATE Event_Summaries SET DayEventDiskSpace=COALESCE(DayEventDiskSpace,0)+COALESCE(NEW.DiskSpace,0) WHERE Event_Summaries.MonitorId=NEW.MonitorId;
      ELSE
        UPDATE Event_Summaries SET DayEventDiskSpace=GREATEST(COALESCE(DayEventDiskSpace,0)+diff,0) WHERE Event_Summaries.MonitorId=NEW.MonitorId;
      END IF;
    END IF;
  END;
  //


DROP TRIGGER IF EXISTS Events_Week_delete_trigger//
CREATE TRIGGER Events_Week_delete_trigger BEFORE DELETE ON Events_Week
FOR EACH ROW BEGIN
  UPDATE Event_Summaries SET
  WeekEvents = GREATEST(COALESCE(WeekEvents,1)-1,0),
  WeekEventDiskSpace=GREATEST(COALESCE(WeekEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
  WHERE Event_Summaries.MonitorId=OLD.MonitorId;
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
        UPDATE Event_Summaries SET WeekEventDiskSpace=GREATEST(COALESCE(WeekEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0) WHERE Event_Summaries.MonitorId=OLD.MonitorId;
        UPDATE Event_Summaries SET WeekEventDiskSpace=COALESCE(WeekEventDiskSpace,0)+COALESCE(NEW.DiskSpace,0) WHERE Event_Summaries.MonitorId=NEW.MonitorId;
      ELSE
        UPDATE Event_Summaries SET WeekEventDiskSpace=GREATEST(COALESCE(WeekEventDiskSpace,0)+diff,0) WHERE Event_Summaries.MonitorId=NEW.MonitorId;
      END IF;
    END IF;
  END;
  //

DROP TRIGGER IF EXISTS Events_Month_delete_trigger//
CREATE TRIGGER Events_Month_delete_trigger BEFORE DELETE ON Events_Month
FOR EACH ROW BEGIN
  UPDATE Event_Summaries SET
  MonthEvents = GREATEST(COALESCE(MonthEvents,1)-1,0),
  MonthEventDiskSpace=GREATEST(COALESCE(MonthEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
  WHERE Event_Summaries.MonitorId=OLD.MonitorId;
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
        UPDATE Event_Summaries SET MonthEventDiskSpace=GREATEST(COALESCE(MonthEventDiskSpace,0)-COALESCE(OLD.DiskSpace),0) WHERE Event_Summaries.MonitorId=OLD.MonitorId;
        UPDATE Event_Summaries SET MonthEventDiskSpace=COALESCE(MonthEventDiskSpace,0)+COALESCE(NEW.DiskSpace) WHERE Event_Summaries.MonitorId=NEW.MonitorId;
      ELSE
        UPDATE Event_Summaries SET MonthEventDiskSpace=GREATEST(COALESCE(MonthEventDiskSpace,0)+diff,0) WHERE Event_Summaries.MonitorId=NEW.MonitorId;
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
      UPDATE Storage SET DiskSpace = GREATEST(COALESCE(DiskSpace,0) + diff,0) WHERE Storage.Id = OLD.StorageId;
    END IF;
  ELSE
    IF ( NEW.DiskSpace ) THEN
      UPDATE Storage SET DiskSpace = COALESCE(DiskSpace,0) + NEW.DiskSpace WHERE Storage.Id = NEW.StorageId;
    END IF;
    IF ( OLD.DiskSpace ) THEN
      UPDATE Storage SET DiskSpace = GREATEST(COALESCE(DiskSpace,0) - OLD.DiskSpace,0) WHERE Storage.Id = OLD.StorageId;
    END IF;
  END IF;

  UPDATE Events_Hour SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
  UPDATE Events_Day SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
  UPDATE Events_Week SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
  UPDATE Events_Month SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;

  IF ( NEW.Archived != OLD.Archived ) THEN
    IF ( NEW.Archived ) THEN
      INSERT INTO Events_Archived (EventId,MonitorId,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.DiskSpace);
      UPDATE Event_Summaries SET ArchivedEvents = COALESCE(ArchivedEvents,0)+1, ArchivedEventDiskSpace = COALESCE(ArchivedEventDiskSpace,0) + COALESCE(NEW.DiskSpace,0) WHERE Event_Summaries.MonitorId=NEW.MonitorId;
    ELSEIF ( OLD.Archived ) THEN
      DELETE FROM Events_Archived WHERE EventId=OLD.Id;
      UPDATE Event_Summaries
        SET
          ArchivedEvents = GREATEST(COALESCE(ArchivedEvents,0)-1,0),
          ArchivedEventDiskSpace = GREATEST(COALESCE(ArchivedEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0),0)
        WHERE Event_Summaries.MonitorId=OLD.MonitorId;
    ELSE
      IF ( OLD.DiskSpace != NEW.DiskSpace ) THEN
        UPDATE Events_Archived SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
        UPDATE Event_Summaries SET
          ArchivedEventDiskSpace = GREATEST(COALESCE(ArchivedEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0) + COALESCE(NEW.DiskSpace,0),0)
          WHERE Event_Summaries.MonitorId=OLD.MonitorId;
      END IF;
    END IF;
  ELSEIF ( NEW.Archived AND diff ) THEN
    UPDATE Events_Archived SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
  END IF;

  IF ( diff ) THEN
    UPDATE Event_Summaries
      SET
        TotalEventDiskSpace = GREATEST(COALESCE(TotalEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0) + COALESCE(NEW.DiskSpace,0),0)
      WHERE Event_Summaries.MonitorId=OLD.MonitorId;
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

  INSERT INTO Events_Hour (EventId,MonitorId,StartDateTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartDateTime,0);
  INSERT INTO Events_Day (EventId,MonitorId,StartDateTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartDateTime,0);
  INSERT INTO Events_Week (EventId,MonitorId,StartDateTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartDateTime,0);
  INSERT INTO Events_Month (EventId,MonitorId,StartDateTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartDateTime,0);
  UPDATE Event_Summaries SET
  HourEvents = COALESCE(HourEvents,0)+1,
  DayEvents = COALESCE(DayEvents,0)+1,
  WeekEvents = COALESCE(WeekEvents,0)+1,
  MonthEvents = COALESCE(MonthEvents,0)+1,
  TotalEvents = COALESCE(TotalEvents,0)+1
  WHERE Event_Summaries.MonitorId=NEW.MonitorId;
END;
//

DROP TRIGGER IF EXISTS event_delete_trigger//

CREATE TRIGGER event_delete_trigger BEFORE DELETE ON Events
FOR EACH ROW
BEGIN
  IF ( OLD.DiskSpace ) THEN
    UPDATE Storage SET DiskSpace = GREATEST(COALESCE(DiskSpace,0) - COALESCE(OLD.DiskSpace,0),0) WHERE Storage.Id = OLD.StorageId;
  END IF;
  DELETE FROM Events_Hour WHERE EventId=OLD.Id;
  DELETE FROM Events_Day WHERE EventId=OLD.Id;
  DELETE FROM Events_Week WHERE EventId=OLD.Id;
  DELETE FROM Events_Month WHERE EventId=OLD.Id;
  IF ( OLD.Archived ) THEN
    DELETE FROM Events_Archived WHERE EventId=OLD.Id;
    UPDATE Event_Summaries SET
      ArchivedEvents = GREATEST(COALESCE(ArchivedEvents,1) - 1,0),
      ArchivedEventDiskSpace = GREATEST(COALESCE(ArchivedEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0),0),
      TotalEvents = GREATEST(COALESCE(TotalEvents,1) - 1,0),
      TotalEventDiskSpace = GREATEST(COALESCE(TotalEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0),0)
      WHERE Event_Summaries.MonitorId=OLD.MonitorId;
  ELSE
    UPDATE Event_Summaries SET
    TotalEvents = GREATEST(COALESCE(TotalEvents,1)-1,0),
    TotalEventDiskSpace=GREATEST(COALESCE(TotalEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
    WHERE Event_Summaries.MonitorId=OLD.MonitorId;
  END IF;
END;

//

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
