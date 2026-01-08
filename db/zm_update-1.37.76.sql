
--
-- This updates a 1.37.75 database to 1.37.76
--
-- Remove the orphaned MonitorIds column from the Users table.
-- This field was removed from zm_create.sql.in but was never dropped
-- for existing installations. Data was migrated to Monitors_Permissions
-- table in zm_update-1.37.27.sql
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
      AND table_name = 'Users'
      AND column_name = 'MonitorIds'
    ) > 0
    ,
    "ALTER TABLE Users DROP COLUMN MonitorIds",
    "SELECT 'Users MonitorIds column already removed.'"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_name = 'Monitor_Status' and index_name = 'Monitor_Status_UpdatedOn_idx' and table_schema = database());
set @sqlstmt := if( @exist > 0, 'DROP INDEX Monitor_Status_UpdatedOn_idx ON Monitor_Status', "SELECT 'Monitor_Status_UpdatedOn_idx INDEX is already removed.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
