
set @exist := (select count(*) from information_schema.statistics where table_name = 'Events_Hour' and index_name = 'Events_Hour_MonitorId_StartTime_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, 'DROP INDEX Events_Hour_MonitorId_StartTime_idx ON Events_Hour', "SELECT 'Events_Hour_MonitorId_StartTime_idx INDEX is already removed.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Events_Hour' and index_name = 'Events_Hour_MonitorId_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, "SELECT 'Events_Hour_MonitorId_idx INDEX already exists.'", "CREATE INDEX `Events_Hour_MonitorId_idx` ON `Events_Hour` (`MonitorId`)");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Events_Hour' and index_name = 'Events_Hour_StartTime_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, "SELECT 'Events_Hour_StartTime_idx INDEX already exists.'", "CREATE INDEX `Events_Hour_StartTime_idx` ON `Events_Hour` (`StartTime`)");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Events_Day' and index_name = 'Events_Day_MonitorId_StartTime_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, 'DROP INDEX Events_Day_MonitorId_StartTime_idx ON Events_Day', "SELECT 'Events_Day_MonitorId_StartTime_idx INDEX is already removed.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Events_Day' and index_name = 'Events_Day_MonitorId_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, "SELECT 'Events_Day_MonitorId_idx INDEX already exists.'", "CREATE INDEX `Events_Day_MonitorId_idx` ON `Events_Day` (`MonitorId`)");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Events_Day' and index_name = 'Events_Day_StartTime_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, "SELECT 'Events_Day_StartTime_idx INDEX already exists.'", "CREATE INDEX `Events_Day_StartTime_idx` ON `Events_Day` (`StartTime`)");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Events_Week' and index_name = 'Events_Week_MonitorId_StartTime_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, 'DROP INDEX Events_Week_MonitorId_StartTime_idx ON Events_Week', "SELECT 'Events_Week_MonitorId_StartTime_idx INDEX is already removed.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Events_Week' and index_name = 'Events_Week_MonitorId_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, "SELECT 'Events_Week_MonitorId_idx INDEX already exists.'", "CREATE INDEX `Events_Week_MonitorId_idx` ON `Events_Week` (`MonitorId`)");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Events_Week' and index_name = 'Events_Week_StartTime_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, "SELECT 'Events_Week_StartTime_idx INDEX already exists.'", "CREATE INDEX `Events_Week_StartTime_idx` ON `Events_Week` (`StartTime`)");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;


set @exist := (select count(*) from information_schema.statistics where table_name = 'Events_Month' and index_name = 'Events_Month_MonitorId_StartTime_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, 'DROP INDEX Events_Month_MonitorId_StartTime_idx ON Events_Month', "SELECT 'Events_Month_MonitorId_StartTime_idx INDEX is already removed.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Events_Month' and index_name = 'Events_Month_MonitorId_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, "SELECT 'Events_Month_MonitorId_idx INDEX already exists.'", "CREATE INDEX `Events_Month_MonitorId_idx` ON `Events_Month` (`MonitorId`)");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Events_Month' and index_name = 'Events_Month_StartTime_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, "SELECT 'Events_Month_StartTime_idx INDEX already exists.'", "CREATE INDEX `Events_Month_StartTime_idx` ON `Events_Month` (`StartTime`)");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
