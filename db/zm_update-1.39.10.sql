--
-- This updates a 1.39.9 database to 1.39.10
--
-- Add a composite secondary index to increase query processing speed
-- without rebuilding the table by changing the primary key.
--
ALTER TABLE `Logs` ADD INDEX `idx_logs_id_component_timekey_level` (`Id`, `Component`, `TimeKey`, `Level`);
