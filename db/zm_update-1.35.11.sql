/* Change Id type to BIGINT. */
set @exist := (SELECT count(*) FROM INFORMATION_SCHEMA.COLUMNS    WHERE table_name = 'Events' AND COLUMN_NAME = 'Id' and DATA_TYPE='bigint');

set @sqlstmt := if( @exist = 0, "ALTER TABLE Events MODIFY Id bigint unsigned NOT NULL auto_increment", "SELECT 'Events.Id is already BIGINT'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

/* Add FOREIGN KEYS After deleting lost records */
set @exist := (select count(*) FROM information_schema.key_column_usage where table_name='Frames' and column_name='EventId' and referenced_table_name='Events' and referenced_column_name='Id');

set @sqlstmt := if( @exist > 1, "SELECT 'You have more than 1 FOREIGN KEY. Please do manual cleanup'", "SELECT 'Ok'");
set @sqlstmt := if( @exist = 1, "SELECT 'FOREIGN KEY EventId in Frames already exists'", @sqlstmt);
set @sqlstmt := if( @exist = 0, "SELECT 'Adding foreign key for EventId to Frames'", @sqlstmt);
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @sqlstmt := if( @exist = 0, "SELECT 'Deleting unlinked Frames'", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
set @sqlstmt := if( @exist = 0, "DELETE FROM Frames WHERE EventId NOT IN (SELECT Id FROM Events)", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
set @sqlstmt := if( @exist = 0, "ALTER TABLE Frames ADD FOREIGN KEY (EventId) REFERENCES Events (Id) ON DELETE CASCADE", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;


set @exist := (select count(*) FROM information_schema.key_column_usage where table_name='Stats' and column_name='EventId' and referenced_table_name='Events' and referenced_column_name='Id');
set @sqlstmt := if( @exist > 1, "SELECT 'You have more than 1 FOREIGN KEY. Please do manual cleanup'", "SELECT 'Ok'");
set @sqlstmt := if( @exist = 1, "SELECT 'FOREIGN KEY EventId in Stats already exists'", @sqlstmt);
set @sqlstmt := if( @exist = 0, "SELECT 'Adding FOREIGN KEY for EventId to Stats'", @sqlstmt);
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @sqlstmt := if( @exist = 0, "SELECT 'Deleting unlinked Stats'", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
set @sqlstmt := if( @exist = 0, "DELETE FROM Stats WHERE EventId NOT IN (SELECT Id FROM Events);", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @sqlstmt := if( @exist = 0, "ALTER TABLE Stats ADD FOREIGN KEY (EventId) REFERENCES Events (Id) ON DELETE CASCADE", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;


set @exist := (select count(*) FROM information_schema.key_column_usage where table_name='Stats' and column_name='MonitorId' and referenced_table_name='Monitors' and referenced_column_name='Id');
set @sqlstmt := if( @exist > 1, "SELECT 'You have more than 1 FOREIGN KEY. Please do manual cleanup'", "SELECT 'Ok'");
set @sqlstmt := if( @exist = 1, "SELECT 'FOREIGN KEY for MonitorId in Stats already exists'", @sql_stmt);
set @sqlstmt := if( @exist = 0, "SELECT 'Adding FOREIGN KEY for MonitorId to Stats'", @sqlstmt);
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @sqlstmt := if( @exist = 0, "SELECT 'Deleting unlinked Stats'", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
set @sqlstmt := if( @exist = 0, "DELETE FROM Stats WHERE MonitorId NOT IN (SELECT Id FROM Monitors);", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @sqlstmt := if( @exist = 0, "ALTER TABLE Stats ADD FOREIGN KEY (MonitorId) REFERENCES Monitors (Id) ON DELETE CASCADE", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) FROM information_schema.key_column_usage where table_name='Stats' and column_name='ZoneId' and referenced_table_name='Zones' and referenced_column_name='Id');
set @sqlstmt := if( @exist > 1, "SELECT 'You have more than 1 FOREIGN KEY. Please do manual cleanup'", "SELECT 'Ok'");
set @sqlstmt := if( @exist = 1, "SELECT 'FOREIGN KEY for ZoneId in Stats already exists'", @sqlstmt);
set @sqlstmt := if( @exist = 0, "SELECT 'Adding foreign key for ZoneId to Stats'", @sqlstmt);
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @sqlstmt := if( @exist = 0, "SELECT 'Deleting unlinked Stats'", "SELECT 'Ok'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
set @sqlstmt := if( @exist = 0, "DELETE FROM Stats WHERE ZoneId NOT IN (SELECT Id FROM Zones);", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
set @sqlstmt := if( @exist = 0, "ALTER TABLE Stats ADD FOREIGN KEY (ZoneId) REFERENCES Zones (Id) ON DELETE CASCADE", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

SELECT 'Adding foreign key for MonitorId to Zones';
set @exist := (select count(*) FROM information_schema.key_column_usage where table_name='Zones' and column_name='MonitorId' and referenced_table_name='Monitors' and referenced_column_name='Id');
set @sqlstmt := if( @exist > 1, "SELECT 'You have more than 1 FOREIGN KEY. Please do manual cleanup'", "SELECT 'Ok'");
set @sqlstmt := if( @exist = 1, "SELECT 'FOREIGN KEY for MonitorId in Zones already exists'", @sqlstmnt);
set @sqlstmt := if( @exist = 0, "SELECT 'Adding foreign key for MonitorId in Zones'", @sqlstmnt);

/*"SELECT 'FOREIGN KEY for MonitorId in Zones does not already exist'");*/
set @badzones := (select count(*) FROM Zones WHERE MonitorId NOT IN (SELECT Id FROM Monitors));
set @sqlstmt := if ( @badzones > 0, "SELECT 'You have Zones with no Monitor record in the Monitors table. Please delete them manually'", "ALTER TABLE Zones ADD FOREIGN KEY (MonitorId) REFERENCES Monitors (Id)");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
