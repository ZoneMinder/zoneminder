--
-- This updates a 1.30.10 database to 1.30.11
--
-- Add StateId Column to Events.
--

ALTER TABLE Monitors MODIFY Path VARCHAR(255);
