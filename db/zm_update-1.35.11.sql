/* Change Id type to BIGINT. */
ALTER TABLE Events MODIFY Id bigint unsigned NOT NULL auto_increment;

/* Add FOREIGN KEYS After deleting lost records */
DELETE FROM Frames WHERE EventId NOT IN (SELECT Id FROM Events);
ALTER TABLE Frames ADD FOREIGN KEY (EventId) REFERENCES Events (Id) ON DELETE CASCADE;

/* Add FOREIGN KEYS After deleting lost records */
DELETE FROM Stats WHERE EventId NOT IN (SELECT Id FROM Events);
ALTER TABLE Stats ADD FOREIGN KEY (EventId) REFERENCES Events (Id) ON DELETE CASCADE;

DELETE FROM Stats WHERE MonitorId NOT IN (SELECT Id FROM Monitors);
ALTER TABLE Stats ADD FOREIGN KEY (`MonitorId`) REFERENCES `Monitors` (`Id`) ON DELETE CASCADE;

DELETE FROM Stats WHERE ZoneId NOT IN (SELECT Id FROM Zones);
ALTER TABLE Stats ADD FOREIGN KEY (`ZoneId`) REFERENCES `Zones` (`Id`) ON DELETE CASCADE;

/* Add FOREIGN KEYS After deleting lost records */
DELETE FROM Zones WHERE MonitorId NOT IN (SELECT Id FROM Monitors);
ALTER TABLE Zones ADD FOREIGN KEY (`MonitorId`) REFERENCES `Monitors` (`Id`) ON DELETE CASCADE;
