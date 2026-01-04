
set @exist := (select count(*) from information_schema.statistics where table_name = 'Monitor_Status' and index_name = 'Monitor_Status_UpdatedOn_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, 'DROP INDEX Monitor_Status_UpdatedOn_idx ON Monitor_Status', "SELECT 'Monitor_Status_UpdatedOn_idx INDEX is already removed.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
