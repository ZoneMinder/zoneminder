/*
Add the EndTime IS NOT NULL term to the Update Disk Space Filter.
This will only work if they havn't modified the stock filter 
 */
UPDATE Filters SET Query_json='{"terms":[{"attr":"DiskSpace","op":"IS","val":"NULL"},{"cnj":"and","obr":"0","attr":"EndDateTime","op":"IS NOT","val":"NULL","cbr":"0"}]}' WHERE Query_json='{"terms":[{"attr":"DiskSpace","op":"IS","val":"NULL"}]}';

/*
Add the EndTime IS NOT NULL term to the Purge When Full Filter. 
This will only work if they havn't modified the stock filter .
This is important to prevent SQL Errors inserting into Frames table if PurgeWhenFull deletes in-progress events.
 */
UPDATE Filters SET Query_json='{"sort_field":"Id","terms":[{"val":0,"attr":"Archived","op":"="},{"cnj":"and","val":95,"attr":"DiskPercent","op":">="},{"cnj":"and","obr":"0","attr":"EndDateTime","op":"IS NOT","val":"NULL","cbr":"0"}],"limit":100,"sort_asc":1}' WHERE Query_json='{"sort_field":"Id","terms":[{"val":0,"attr":"Archived","op":"="},{"cnj":"and","val":95,"attr":"DiskPercent","op":">="}],"limit":100,"sort_asc":1}';

/* Add FOREIGN KEYS After deleting lost records */
set @exist := (select count(*) FROM information_schema.key_column_usage where table_name='Groups_Monitors' and column_name='GroupId' and referenced_table_name='Groups' and referenced_column_name='Id');

set @sqlstmt := if( @exist > 1, "SELECT 'You have more than 1 FOREIGN KEY. Please do manual cleanup'", "SELECT 'Ok'");
set @sqlstmt := if( @exist = 1, "SELECT 'FOREIGN KEY GroupId in Groups_Monitors already exists'", @sqlstmt);
set @sqlstmt := if( @exist = 0, "SELECT 'Adding foreign key for GroupId to Groups_Monitors'", @sqlstmt);
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @sqlstmt := if( @exist = 0, "SELECT 'Deleting unlinked Groups_Monitors'", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
set @sqlstmt := if( @exist = 0, "DELETE FROM `Groups_Monitors` WHERE `GroupId` NOT IN (SELECT `Id` FROM `Groups`)", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
set @sqlstmt := if( @exist = 0, "ALTER TABLE `Groups_Monitors` ADD FOREIGN KEY (`GroupId`) REFERENCES `Groups` (`Id`) ON DELETE CASCADE", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

/* Add FOREIGN KEYS After deleting lost records */
set @exist := (select count(*) FROM information_schema.key_column_usage where table_name='Groups_Monitors' and column_name='MonitorId' and referenced_table_name='Monitors' and referenced_column_name='Id');

set @sqlstmt := if( @exist > 1, "SELECT 'You have more than 1 FOREIGN KEY. Please do manual cleanup'", "SELECT 'Ok'");
set @sqlstmt := if( @exist = 1, "SELECT 'FOREIGN KEY MonitorId in Groups_Monitors already exists'", @sqlstmt);
set @sqlstmt := if( @exist = 0, "SELECT 'Adding foreign key for MonitorId to Groups_Monitors'", @sqlstmt);
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @sqlstmt := if( @exist = 0, "SELECT 'Deleting unlinked Groups_Monitors'", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
set @sqlstmt := if( @exist = 0, "DELETE FROM `Groups_Monitors` WHERE `MonitorId` NOT IN (SELECT `Id` FROM `Monitors`)", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
set @sqlstmt := if( @exist = 0, "ALTER TABLE `Groups_Monitors` ADD FOREIGN KEY (`MonitorId`) REFERENCES `Monitors` (`Id`) ON DELETE CASCADE", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

/* Add FOREIGN KEYS After deleting lost records */
set @exist := (select count(*) FROM information_schema.key_column_usage where table_name='Groups' and column_name='ParentId' and referenced_table_name='Groups' and referenced_column_name='Id');

set @sqlstmt := if( @exist > 1, "SELECT 'You have more than 1 FOREIGN KEY. Please do manual cleanup'", "SELECT 'Ok'");
set @sqlstmt := if( @exist = 1, "SELECT 'FOREIGN KEY ParentId in Groups already exists'", @sqlstmt);
set @sqlstmt := if( @exist = 0, "SELECT 'Adding foreign key for ParentId to Groups'", @sqlstmt);
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @sqlstmt := if( @exist = 0, "SELECT 'Deleting unlinked Groups'", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
set @sqlstmt := if( @exist = 0, "UPDATE `Groups` SET `ParentId` = NULL WHERE (ParentId IS NOT NULL) and ParentId NOT IN (SELECT * FROM(SELECT Id FROM `Groups`)tblTmp)", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
set @sqlstmt := if( @exist = 0, "ALTER TABLE `Groups` ADD FOREIGN KEY (ParentId) REFERENCES `Groups` (Id) ON DELETE CASCADE", "SELECT '.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

