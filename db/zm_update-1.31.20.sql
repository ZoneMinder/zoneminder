
SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Events_Hour'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Events_Hour table exists'",
    "
CREATE TABLE `Events_Hour` (
  `EventId` int(10) unsigned NOT NULL,
  `MonitorId` int(10) unsigned NOT NULL,
  `StartTime` datetime default NULL,
  `DiskSpace`   bigint unsigned default NULL,
  PRIMARY KEY (`EventId`),
  KEY `Events_Hour_MonitorId_StartTime_idx` (`MonitorId`,`StartTime`)
);
"
  ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Events_Day'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Events_Day table exists'",
    "
CREATE TABLE `Events_Day` (
  `EventId` int(10) unsigned NOT NULL,
  `MonitorId` int(10) unsigned NOT NULL,
  `StartTime` datetime default NULL,
  `DiskSpace`   bigint unsigned default NULL,
  PRIMARY KEY (`EventId`),
  KEY `Events_Day_MonitorId_StartTime_idx` (`MonitorId`,`StartTime`)
);
"));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Events_Week'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Events_Week table exists'",
    "
CREATE TABLE `Events_Week` (
  `EventId` int(10) unsigned NOT NULL,
  `MonitorId` int(10) unsigned NOT NULL,
  `StartTime` datetime default NULL,
  `DiskSpace`   bigint unsigned default NULL,
  PRIMARY KEY (`EventId`),
  KEY `Events_Week_MonitorId_StartTime_idx` (`MonitorId`,`StartTime`)
);
"));

PREPARE stmt FROM @s;
EXECUTE stmt;


SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Events_Month'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Events_Month table exists'",
    "
CREATE TABLE `Events_Month` (
  `EventId` int(10) unsigned NOT NULL,
  `MonitorId` int(10) unsigned NOT NULL,
  `StartTime` datetime default NULL,
  `DiskSpace`   bigint unsigned default NULL,
  PRIMARY KEY (`EventId`),
  KEY `Events_Month_MonitorId_StartTime_idx` (`MonitorId`,`StartTime`)
);
"));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Events_Archived'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Events_Archived table exists'",
    "
CREATE TABLE `Events_Archived` (
  `EventId` int(10) unsigned NOT NULL,
  `MonitorId` int(10) unsigned NOT NULL,
  `DiskSpace`   bigint unsigned default NULL,
  PRIMARY KEY (`EventId`),
  KEY `Events_Month_MonitorId_idx` (`MonitorId`)
);
"));

PREPARE stmt FROM @s;
EXECUTE stmt;

drop trigger if exists event_update_trigger;

delimiter //

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
      UPDATE Monitors SET Events_Archived_Count = Events_Archived_Count+1, Events_Archived_DiskSpace = Events_Archived_DiskSpace + NEW.DiskSpace WHERE Id=NEW.MonitorId;
    ELSEIF ( OLD.Archived ) THEN
      DELETE FROM Events_Archived WHERE EventId=OLD.Id;
      UPDATE Monitors SET Events_Archived_Count = Events_Archived_Count-1, Events_Archived_DiskSpace = Events_Archived_DiskSpace - OLD.DiskSpace WHERE Id=OLD.MonitorId;
    ELSE
      IF ( OLD.DiskSpace != NEW.DiskSpace ) THEN
        UPDATE Events_Archived SET DiskSpace=NEW.DiskSpace WHERE EventId=NEW.Id;
        UPDATE Monitors SET
          Events_Archived_DiskSpace = Events_Archived_DiskSpace - OLD.DiskSpace + NEW.DiskSpace
          WHERE Id=OLD.MonitorId;
      END IF;
    END IF;
  END IF;

  IF ( OLD.DiskSpace != NEW.DiskSpace ) THEN
    UPDATE Monitors SET Events_Total_DiskSpace = Events_Total_DiskSpace - OLD.DiskSpace + NEW.DiskSpace WHERE Id=OLD.MonitorId;
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
      Events_Archived = Events_Archived - 1,
      Events_Archived_DiskSpace = Events_Archived_DiskSpace - OLD.DiskSpace,
      Events_Total_Count = Events_Total_Count - 1,
      Events_Total_DiskSpace = Events_Total_DiskSpace - OLD.DiskSpace
      WHERE Id=OLD.MonitorId;
  ELSE
    UPDATE Monitors SET
    Events_Total_Count = Events_Total_Count-1,
    Events_Total_DiskSpace=Events_Total_DiskSpace-OLD.DiskSpace
    WHERE Id=OLD.MonitorId;
  END IF;
END;

//

delimiter ;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Storage'
      AND column_name = 'zmstats.pl'
    ) > 0,
    "SELECT 'Column zmstats.pl already exists in Storage'",
    "ALTER TABLE Storage ADD `zmstats.pl` BOOLEAN NOT NULL DEFAULT FALSE AFTER `DiskSpace`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Storage'
      AND column_name = 'zmaudit.pl'
    ) > 0,
    "SELECT 'Column zmaudit.pl already exists in Storage'",
    "ALTER TABLE Storage ADD `zmaudit.pl` BOOLEAN NOT NULL DEFAULT FALSE AFTER `zmstats.pl`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Storage'
      AND column_name = 'zmtrigger.pl'
    ) > 0,
    "SELECT 'Column zmtriger.pl already exists in Storage'",
    "ALTER TABLE Storage ADD `zmtrigger.pl` BOOLEAN NOT NULL DEFAULT FALSE AFTER `zmaudit.pl`"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;
