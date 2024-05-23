/* This was done in 1.31.0 but zm_create.sql.in wasn't updated to match. */
ALTER TABLE Monitors MODIFY LinkedMonitors varchar(255);
