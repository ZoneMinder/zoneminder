
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

drop trigger if exists event_update_trigger;

delimiter //

create trigger event_update_trigger after update on Events 
for each row
begin
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

end;

//

delimiter ;

drop trigger if exists event_insert_trigger;

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

create trigger event_delete_trigger

before delete

on Events

for each row

begin

  call update_storage_stats(OLD.StorageId, -OLD.DiskSpace);
  DELETE FROM Events_Hour WHERE EventId=OLD.Id;
  DELETE FROM Events_Day WHERE EventId=OLD.Id;
  DELETE FROM Events_Week WHERE EventId=OLD.Id;
  DELETE FROM Events_Month WHERE EventId=OLD.Id;
end;

//

delimiter ;

