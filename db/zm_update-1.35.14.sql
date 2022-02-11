SELECT 'This update may make changes that require SUPER privileges. If you see an error message saying:

ERROR 1419 (HY000) at line 298: You do not have the SUPER privilege and binary logging is enabled (you *might* want to use the less safe log_bin_trust_function_creators variable)

You will have to either run this update as root manually using something like (on ubuntu/debian)

sudo mysql --defaults-file=/etc/mysql/debian.cnf zm < /usr/share/zoneminder/db/zm_update-1.35.14.sql

OR

sudo mysql --defaults-file=/etc/mysql/debian.cnf "set global log_bin_trust_function_creators=1;"
sudo zmupdate.pl
';

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'TotalEvents'
    ) > 0,
"SELECT 'Column TotalEvents already exists in Monitor_Status'",
"ALTER TABLE `Monitor_Status` ADD `TotalEvents` INT(10) AFTER `CaptureBandwidth`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'TotalEvents'
    ) > 0,
"ALTER TABLE `Monitors` DROP `TotalEvents`",
"SELECT 'Column TotalEvents is already removed from Monitors'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'TotalEventDiskSpace'
    ) > 0,
"SELECT 'Column TotalEventDiskSpace already exists in Monitor_Status'",
"ALTER TABLE `Monitor_Status` ADD `TotalEventDiskSpace` BIGINT AFTER `TotalEvents`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'TotalEventDiskSpace'
    ) > 0,
"ALTER TABLE `Monitors` DROP `TotalEventDiskSpace`",
"SELECT 'Column TotalEventDiskSpace is already removed from Monitors'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'HourEvents'
    ) > 0,
"SELECT 'Column HourEvents already exists in Monitors'",
"ALTER TABLE `Monitor_Status` ADD `HourEvents` INT(10) AFTER `TotalEventDiskSpace`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'HourEvents'
    ) > 0,
"ALTER TABLE `Monitors` DROP `HourEvents`",
"SELECT 'Column HourEvents is already removed from Monitors'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'HourEventDiskSpace'
    ) > 0,
"SELECT 'Column HourEventDiskSpace already exists in Monitor_Status'",
"ALTER TABLE `Monitor_Status` ADD `HourEventDiskSpace` BIGINT AFTER `HourEvents`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'HourEventDiskSpace'
    ) > 0,
"ALTER TABLE `Monitors` DROP `HourEventDiskSpace`",
"SELECT 'Column HourEventDiskSpace is already removed from Monitors'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'DayEvents'
    ) > 0,
"SELECT 'Column DayEvents already exists in Monitor_Status'",
"ALTER TABLE `Monitor_Status` ADD `DayEvents` INT(10) AFTER `HourEventDiskSpace`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'DayEvents'
    ) > 0,
"ALTER TABLE `Monitors` DROP `DayEvents`",
"SELECT 'Column DayEvents is already removed from Monitors'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'DayEventDiskSpace'
    ) > 0,
"SELECT 'Column DayEventDiskSpace already exists in Monitor_Status'",
"ALTER TABLE `Monitor_Status` ADD `DayEventDiskSpace` BIGINT AFTER `DayEvents`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'DayEventDiskSpace'
    ) > 0,
"ALTER TABLE `Monitors` DROP `DayEventDiskSpace`",
"SELECT 'Column DayEventDiskSpace is already removed from Monitors'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'WeekEvents'
    ) > 0,
"SELECT 'Column WeekEvents already exists in Monitor_Status'",
"ALTER TABLE `Monitor_Status` ADD `WeekEvents` INT(10) AFTER `DayEventDiskSpace`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'WeekEvents'
    ) > 0,
"ALTER TABLE `Monitors` DROP `WeekEvents`",
"SELECT 'Column WeekEvents is already removed from Monitors'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'WeekEventDiskSpace'
    ) > 0,
"SELECT 'Column WeekEventDiskSpace already exists in Monitor_Status'",
"ALTER TABLE `Monitor_Status` ADD `WeekEventDiskSpace` BIGINT AFTER `WeekEvents`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'WeekEventDiskSpace'
    ) > 0,
"ALTER TABLE `Monitors` DROP `WeekEventDiskSpace`",
"SELECT 'Column WeekEventDiskSpace is already removed from Monitors'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'MonthEvents'
    ) > 0,
"SELECT 'Column MonthEvents already exists in Monitor_Status'",
"ALTER TABLE `Monitor_Status` ADD `MonthEvents` INT(10) AFTER `WeekEventDiskSpace`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'MonthEvents'
    ) > 0,
"ALTER TABLE `Monitors` DROP `MonthEvents`",
"SELECT 'Column MonthEvents is already removed from Monitors'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'MonthEventDiskSpace'
    ) > 0,
"SELECT 'Column MonthEventDiskSpace already exists in Monitor_Status'",
"ALTER TABLE `Monitor_Status` ADD `MonthEventDiskSpace` BIGINT AFTER `MonthEvents`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'MonthEventDiskSpace'
    ) > 0,
"ALTER TABLE `Monitors` DROP `MonthEventDiskSpace`",
"SELECT 'Column MonthEventDiskSpace is already removed from Monitors'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'ArchivedEvents'
    ) > 0,
"SELECT 'Column ArchivedEvents already exists in Monitor_Status'",
"ALTER TABLE `Monitor_Status` ADD `ArchivedEvents` INT(10) AFTER `MonthEventDiskSpace`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ArchivedEvents'
    ) > 0,
"ALTER TABLE `Monitors` DROP `ArchivedEvents`",
"SELECT 'Column ArchivedEvents is already removed from Monitors'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'ArchivedEventDiskSpace'
    ) > 0,
"SELECT 'Column ArchivedEventDiskSpace already exists in Monitor_Status'",
"ALTER TABLE `Monitor_Status` ADD `ArchivedEventDiskSpace` BIGINT AFTER `ArchivedEvents`"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'ArchivedEventDiskSpace'
    ) > 0,
"ALTER TABLE `Monitors` DROP `ArchivedEventDiskSpace`",
"SELECT 'Column ArchivedEventDiskSpace is already removed from Monitors'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;

UPDATE Monitor_Status INNER JOIN (
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
    ) AS E ON E.MonitorId=Monitor_Status.MonitorId SET
    Monitor_Status.TotalEvents = E.TotalEvents,
    Monitor_Status.TotalEventDiskSpace = E.TotalEventDiskSpace,
    Monitor_Status.ArchivedEvents = E.ArchivedEvents,
    Monitor_Status.ArchivedEventDiskSpace = E.ArchivedEventDiskSpace,
    Monitor_Status.HourEvents = E.HourEvents,
    Monitor_Status.HourEventDiskSpace = E.HourEventDiskSpace,
    Monitor_Status.DayEvents = E.DayEvents,
    Monitor_Status.DayEventDiskSpace = E.DayEventDiskSpace,
    Monitor_Status.WeekEvents = E.WeekEvents,
    Monitor_Status.WeekEventDiskSpace = E.WeekEventDiskSpace,
    Monitor_Status.MonthEvents = E.MonthEvents,
    Monitor_Status.MonthEventDiskSpace = E.MonthEventDiskSpace;


delimiter //
DROP TRIGGER IF EXISTS Events_Hour_delete_trigger//
CREATE TRIGGER Events_Hour_delete_trigger BEFORE DELETE ON Events_Hour
FOR EACH ROW BEGIN
  UPDATE Monitor_Status SET
  HourEvents = GREATEST(COALESCE(HourEvents,1)-1,0),
  HourEventDiskSpace=GREATEST(COALESCE(HourEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
  WHERE Monitor_Status.MonitorId=OLD.MonitorId;
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
        UPDATE Monitor_Status SET HourEventDiskSpace=GREATEST(COALESCE(HourEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0) WHERE Monitor_Status.MonitorId=OLD.MonitorId;
        UPDATE Monitor_Status SET HourEventDiskSpace=COALESCE(HourEventDiskSpace,0)+COALESCE(NEW.DiskSpace,0) WHERE Monitor_Status.MonitorId=NEW.MonitorId;
      ELSE
        UPDATE Monitor_Status SET HourEventDiskSpace=COALESCE(HourEventDiskSpace,0)+diff WHERE Monitor_Status.MonitorId=NEW.MonitorId;
      END IF;
    END IF;
  END;
//

DROP TRIGGER IF EXISTS Events_Day_delete_trigger//
CREATE TRIGGER Events_Day_delete_trigger BEFORE DELETE ON Events_Day
FOR EACH ROW BEGIN
  UPDATE Monitor_Status SET
  DayEvents = GREATEST(COALESCE(DayEvents,1)-1,0),
  DayEventDiskSpace=GREATEST(COALESCE(DayEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
  WHERE Monitor_Status.MonitorId=OLD.MonitorId;
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
        UPDATE Monitor_Status SET DayEventDiskSpace=GREATEST(COALESCE(DayEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0) WHERE Monitor_Status.MonitorId=OLD.MonitorId;
        UPDATE Monitor_Status SET DayEventDiskSpace=COALESCE(DayEventDiskSpace,0)+COALESCE(NEW.DiskSpace,0) WHERE Monitor_Status.MonitorId=NEW.MonitorId;
      ELSE
        UPDATE Monitor_Status SET DayEventDiskSpace=GREATEST(COALESCE(DayEventDiskSpace,0)+diff,0) WHERE Monitor_Status.MonitorId=NEW.MonitorId;
      END IF;
    END IF;
  END;
  //


DROP TRIGGER IF EXISTS Events_Week_delete_trigger//
CREATE TRIGGER Events_Week_delete_trigger BEFORE DELETE ON Events_Week
FOR EACH ROW BEGIN
  UPDATE Monitor_Status SET
  WeekEvents = GREATEST(COALESCE(WeekEvents,1)-1,0),
  WeekEventDiskSpace=GREATEST(COALESCE(WeekEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
  WHERE Monitor_Status.MonitorId=OLD.MonitorId;
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
        UPDATE Monitor_Status SET WeekEventDiskSpace=GREATEST(COALESCE(WeekEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0) WHERE Monitor_Status.MonitorId=OLD.MonitorId;
        UPDATE Monitor_Status SET WeekEventDiskSpace=COALESCE(WeekEventDiskSpace,0)+COALESCE(NEW.DiskSpace,0) WHERE Monitor_Status.MonitorId=NEW.MonitorId;
      ELSE
        UPDATE Monitor_Status SET WeekEventDiskSpace=GREATEST(COALESCE(WeekEventDiskSpace,0)+diff,0) WHERE Monitor_Status.MonitorId=NEW.MonitorId;
      END IF;
    END IF;
  END;
  //

DROP TRIGGER IF EXISTS Events_Month_delete_trigger//
CREATE TRIGGER Events_Month_delete_trigger BEFORE DELETE ON Events_Month
FOR EACH ROW BEGIN
  UPDATE Monitor_Status SET
  MonthEvents = GREATEST(COALESCE(MonthEvents,1)-1,0),
  MonthEventDiskSpace=GREATEST(COALESCE(MonthEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
  WHERE Monitor_Status.MonitorId=OLD.MonitorId;
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
        UPDATE Monitor_Status SET MonthEventDiskSpace=GREATEST(COALESCE(MonthEventDiskSpace,0)-COALESCE(OLD.DiskSpace),0) WHERE Monitor_Status.MonitorId=OLD.MonitorId;
        UPDATE Monitor_Status SET MonthEventDiskSpace=COALESCE(MonthEventDiskSpace,0)+COALESCE(NEW.DiskSpace) WHERE Monitor_Status.MonitorId=NEW.MonitorId;
      ELSE
        UPDATE Monitor_Status SET MonthEventDiskSpace=GREATEST(COALESCE(MonthEventDiskSpace,0)+diff,0) WHERE Monitor_Status.MonitorId=NEW.MonitorId;
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
      UPDATE Monitor_Status SET ArchivedEvents = COALESCE(ArchivedEvents,0)+1, ArchivedEventDiskSpace = COALESCE(ArchivedEventDiskSpace,0) + COALESCE(NEW.DiskSpace,0) WHERE Monitor_Status.MonitorId=NEW.MonitorId;
    ELSEIF ( OLD.Archived ) THEN
      DELETE FROM Events_Archived WHERE EventId=OLD.Id;
      UPDATE Monitor_Status
        SET
          ArchivedEvents = GREATEST(COALESCE(ArchivedEvents,0)-1,0),
          ArchivedEventDiskSpace = GREATEST(COALESCE(ArchivedEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0),0)
        WHERE Monitor_Status.MonitorId=OLD.MonitorId;
    ELSE
      IF ( OLD.DiskSpace != NEW.DiskSpace ) THEN
        UPDATE Events_Archived SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
        UPDATE Monitor_Status SET
          ArchivedEventDiskSpace = GREATEST(COALESCE(ArchivedEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0) + COALESCE(NEW.DiskSpace,0),0)
          WHERE Monitor_Status.MonitorId=OLD.MonitorId;
      END IF;
    END IF;
  ELSEIF ( NEW.Archived AND diff ) THEN
    UPDATE Events_Archived SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
  END IF;

  IF ( diff ) THEN
    UPDATE Monitor_Status
      SET
        TotalEventDiskSpace = GREATEST(COALESCE(TotalEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0) + COALESCE(NEW.DiskSpace,0),0)
      WHERE Monitor_Status.MonitorId=OLD.MonitorId;
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
  UPDATE Monitor_Status SET
  HourEvents = COALESCE(HourEvents,0)+1,
  DayEvents = COALESCE(DayEvents,0)+1,
  WeekEvents = COALESCE(WeekEvents,0)+1,
  MonthEvents = COALESCE(MonthEvents,0)+1,
  TotalEvents = COALESCE(TotalEvents,0)+1
  WHERE Monitor_Status.MonitorId=NEW.MonitorId;
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
    UPDATE Monitor_Status SET
      ArchivedEvents = GREATEST(COALESCE(ArchivedEvents,1) - 1,0),
      ArchivedEventDiskSpace = GREATEST(COALESCE(ArchivedEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0),0),
      TotalEvents = GREATEST(COALESCE(TotalEvents,1) - 1,0),
      TotalEventDiskSpace = GREATEST(COALESCE(TotalEventDiskSpace,0) - COALESCE(OLD.DiskSpace,0),0)
      WHERE Monitor_Status.MonitorId=OLD.MonitorId;
  ELSE
    UPDATE Monitor_Status SET
    TotalEvents = GREATEST(COALESCE(TotalEvents,1)-1,0),
    TotalEventDiskSpace=GREATEST(COALESCE(TotalEventDiskSpace,0)-COALESCE(OLD.DiskSpace,0),0)
    WHERE Monitor_Status.MonitorId=OLD.MonitorId;
  END IF;
END;

//

DELIMITER ;

REPLACE INTO Events_Hour SELECT Id,MonitorId,StartDateTime,DiskSpace FROM Events WHERE StartDateTime > DATE_SUB(NOW(),  INTERVAL 1 hour);
REPLACE INTO Events_Day SELECT Id,MonitorId,StartDateTime,DiskSpace FROM Events WHERE StartDateTime > DATE_SUB(NOW(),  INTERVAL 1 day);
REPLACE INTO Events_Week SELECT Id,MonitorId,StartDateTime,DiskSpace FROM Events WHERE StartDateTime > DATE_SUB(NOW(),  INTERVAL 1 week);
REPLACE INTO Events_Month SELECT Id,MonitorId,StartDateTime,DiskSpace FROM Events WHERE StartDateTime > DATE_SUB(NOW(),  INTERVAL 1 month);
REPLACE INTO Events_Archived SELECT Id,MonitorId,DiskSpace FROM Events WHERE Archived=1;

UPDATE Monitor_Status INNER JOIN (
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
    ) AS E ON E.MonitorId=Monitor_Status.MonitorId SET
    Monitor_Status.TotalEvents = E.TotalEvents,
    Monitor_Status.TotalEventDiskSpace = E.TotalEventDiskSpace,
    Monitor_Status.ArchivedEvents = E.ArchivedEvents,
    Monitor_Status.ArchivedEventDiskSpace = E.ArchivedEventDiskSpace,
    Monitor_Status.HourEvents = E.HourEvents,
    Monitor_Status.HourEventDiskSpace = E.HourEventDiskSpace,
    Monitor_Status.DayEvents = E.DayEvents,
    Monitor_Status.DayEventDiskSpace = E.DayEventDiskSpace,
    Monitor_Status.WeekEvents = E.WeekEvents,
    Monitor_Status.WeekEventDiskSpace = E.WeekEventDiskSpace,
    Monitor_Status.MonthEvents = E.MonthEvents,
    Monitor_Status.MonthEventDiskSpace = E.MonthEventDiskSpace;
