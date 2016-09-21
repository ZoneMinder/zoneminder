--
-- This updates a 1.30.6 database to 1.30.7
--
-- Changing StorageId to be NOT NULL and default 0
--

UPDATE Monitors SET StorageId = 0 WHERE StorageId IS NULL;
ALTER TABLE Monitors MODIFY `StorageId`	smallint(5) unsigned NOT NULL default 0;
UPDATE Events SET StorageId = 0 WHERE StorageId IS NULL;
ALTER TABLE Events MODIFY `StorageId`	smallint(5) unsigned NOT NULL default 0;
