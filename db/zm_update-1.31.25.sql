delimiter //

DROP TRIGGER IF EXISTS event_insert_trigger//

create trigger event_insert_trigger after insert on Events
for each row
  begin

  INSERT INTO Events_Hour (EventId,MonitorId,StartTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartTime,0);
  INSERT INTO Events_Day (EventId,MonitorId,StartTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartTime,0);
  INSERT INTO Events_Week (EventId,MonitorId,StartTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartTime,0);
  INSERT INTO Events_Month (EventId,MonitorId,StartTime,DiskSpace) VALUES (NEW.Id,NEW.MonitorId,NEW.StartTime,0);
  UPDATE Monitors SET TotalEvents = TotalEvents+1 WHERE Id=NEW.MonitorId;
end;
//

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
    Monitors.MonthEventDiskSpace = E.MonthEventDiskSpace//

