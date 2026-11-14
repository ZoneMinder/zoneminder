--
-- This updates a 1.39.47 database to 1.39.48
--

--
-- Tags.Name was latin1, so names outside latin1 (CJK, emoji) could not be stored.
--

ALTER TABLE `Tags` MODIFY `Name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '';
