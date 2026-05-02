--
-- This updates a 1.39.5 database to 1.39.6
--
-- Make changes to the Tags table and change the Name column's COLLATE to utf8mb4_bin
--
ALTER TABLE `Tags` MODIFY `Name` VARCHAR(64) COLLATE `utf8mb4_bin` NOT NULL DEFAULT '';
