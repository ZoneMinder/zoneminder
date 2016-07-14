--
-- This updates a 1.30.3 database to 1.30.4
--
-- No changes required
--

ALTER TABLE Monitors MODIFY LabelFormat varchar(64);
ALTER TABLE Monitors MODIFY Host varchar(64);
ALTER TABLE Monitors MODIFY Protocol varchar(16);
ALTER TABLE Monitors MODIFY Options varchar(255);
ALTER TABLE Monitors MODIFY LinkedMonitors varchar(255);
ALTER TABLE Monitors MODIFY User varchar(64);
ALTER TABLE Monitors MODIFY Pass varchar(64);
ALTER TABLE Monitors MODIFY RTSPDescribe tinyint(1) unsigned;
