--
-- This updates a 1.39.9 database to 1.39.10
--
-- Add a composite secondary index to increase query processing speed
-- without rebuilding the table by changing the primary key.
-- Removing Logs_Component_idx because it's now redundant.
--
ALTER TABLE `Logs` ADD INDEX `Logs_Component_Level_TimeKey_Id_idx` (`Component`, `Level`, `TimeKey`, `Id`);
ALTER TABLE `Logs` DROP INDEX `Logs_Component_idx`;
