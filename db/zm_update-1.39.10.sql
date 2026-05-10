--
-- This updates a 1.39.9 database to 1.39.10
--
-- Add Component, TimeKey, and Level to the primary index to increase query processing speed.
--
ALTER TABLE `Logs` DROP PRIMARY KEY, ADD PRIMARY KEY (`Id`, `Component`, `TimeKey`, `Level`);
