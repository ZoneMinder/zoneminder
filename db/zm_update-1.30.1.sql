--
-- This updates a 1.30.0 database to 1.30.1
--
-- Alter type of Messages column from VARCHAR(255) to TEXT
--

ALTER TABLE Logs MODIFY Message TEXT;
