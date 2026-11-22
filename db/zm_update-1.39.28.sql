--
-- This updates a 1.39.27 database to 1.39.28
--
-- Drop secondary indexes that no query uses, or that duplicate another index.
--
-- Logs.TimeKey duplicates Logs_TimeKey_idx, created immediately below it in
-- zm_create.sql.in since 1.31.11. Both exist on every install, fresh and
-- upgraded, and every log INSERT has maintained both ever since.
--
-- Stats.MonitorId and Stats.ZoneId are never read. Every query against Stats is
-- anchored on EventId - the per-frame zone stats view, the ZoneId filter term
-- in FilterTerm.php and Filter.pm, the deletes in Event.php and Event.pm - and
-- EventId_ZoneId serves all of them. A Stats row is written per frame per zone
-- when ZM_RECORD_EVENT_STATS is on, so this is the same insert-rate argument as
-- the Frames indexes in 1.39.27.
--
-- EncoderTemplates.Encoder is the leftmost prefix of Encoder_Name, and the
-- RoleId indexes on the two role permission tables are the leftmost prefix of
-- the UNIQUE index beside them. Those tables are small and rarely written, so
-- this is tidying rather than a saving, folded in while we are here.
--
-- Monitor_Status_UpdatedOn_idx is dropped by zm_update-1.37.76.sql but was left
-- in zm_create.sql.in, so fresh installs have had it and upgraded installs have
-- not. This drop is repeated here only so that the two agree from 1.39.28 on;
-- installs that already ran 1.37.76 will report it as already removed.
--
--
-- Stats.MonitorId and Stats.ZoneId are the only two here with no other index
-- covering their column, so on an install still carrying a foreign key on those
-- columns - 1.37.31 drops them only when they are named Stats_ibfk_1..4 - the
-- drop would fail and abort the upgrade. Those two are guarded to skip instead.
--

set @exist := (select count(*) from information_schema.statistics where table_schema = database() and table_name = 'Logs' and index_name = 'TimeKey');
set @sqlstmt := if( @exist > 0, 'DROP INDEX `TimeKey` ON `Logs`', "SELECT 'TimeKey INDEX is already removed from Logs.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_schema = database() and table_name = 'Stats' and index_name = 'MonitorId');
set @fk := (select count(*) from information_schema.key_column_usage where table_schema = database() and table_name = 'Stats' and column_name = 'MonitorId' and referenced_table_name is not null);
set @sqlstmt := if( @exist = 0, "SELECT 'MonitorId INDEX is already removed from Stats.'", if( @fk > 0, "SELECT 'Keeping Stats.MonitorId: a FOREIGN KEY still needs it.'", 'DROP INDEX `MonitorId` ON `Stats`' ));
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_schema = database() and table_name = 'Stats' and index_name = 'ZoneId');
set @fk := (select count(*) from information_schema.key_column_usage where table_schema = database() and table_name = 'Stats' and column_name = 'ZoneId' and referenced_table_name is not null);
set @sqlstmt := if( @exist = 0, "SELECT 'ZoneId INDEX is already removed from Stats.'", if( @fk > 0, "SELECT 'Keeping Stats.ZoneId: a FOREIGN KEY still needs it.'", 'DROP INDEX `ZoneId` ON `Stats`' ));
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_schema = database() and table_name = 'EncoderTemplates' and index_name = 'Encoder');
set @sqlstmt := if( @exist > 0, 'DROP INDEX `Encoder` ON `EncoderTemplates`', "SELECT 'Encoder INDEX is already removed from EncoderTemplates.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_schema = database() and table_name = 'Role_Groups_Permissions' and index_name = 'Role_Groups_Permissions_RoleId_idx');
set @sqlstmt := if( @exist > 0, 'DROP INDEX `Role_Groups_Permissions_RoleId_idx` ON `Role_Groups_Permissions`', "SELECT 'Role_Groups_Permissions_RoleId_idx INDEX is already removed from Role_Groups_Permissions.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_schema = database() and table_name = 'Role_Monitors_Permissions' and index_name = 'Role_Monitors_Permissions_RoleId_idx');
set @sqlstmt := if( @exist > 0, 'DROP INDEX `Role_Monitors_Permissions_RoleId_idx` ON `Role_Monitors_Permissions`', "SELECT 'Role_Monitors_Permissions_RoleId_idx INDEX is already removed from Role_Monitors_Permissions.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_schema = database() and table_name = 'Monitor_Status' and index_name = 'Monitor_Status_UpdatedOn_idx');
set @sqlstmt := if( @exist > 0, 'DROP INDEX `Monitor_Status_UpdatedOn_idx` ON `Monitor_Status`', "SELECT 'Monitor_Status_UpdatedOn_idx INDEX is already removed from Monitor_Status.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
