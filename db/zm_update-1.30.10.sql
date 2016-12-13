-- This updates a 1.30.9 database to 1.30.10
--
-- Alter type of Messages column from VARCHAR(255) to TEXT
--

ALTER TABLE Logs MODIFY Message TEXT;
