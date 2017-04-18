--
-- This updates a 1.30.0 database to 1.30.1
--
-- DROP NULL FROM various Monitors Columns
--

ALTER TABLE Monitors MODIFY COLUMN LinkedMonitors varchar(255);
ALTER TABLE Monitors MODIFY COLUMN EncoderParameters TEXT;
ALTER TABLE Monitors MODIFY COLUMN LabelFormat varchar(64);
ALTER TABLE Monitors MODIFY COLUMN Options varchar(255);

