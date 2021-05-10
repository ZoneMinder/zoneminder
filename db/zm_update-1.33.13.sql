--
-- Add primary keys for Logs and Stats tables
--

SELECT "Modifying Monitors MaxFPS to DECIMAL(5,3)";
ALTER TABLE `Monitors` MODIFY `MaxFPS` decimal(5,3) default NULL;
