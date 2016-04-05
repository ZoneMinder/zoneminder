--
-- This updates a 1.29.0 database to 1.29.1
--
--

-- Increase the size of the Pid field for FreeBSD
ALTER TABLE Logs MODIFY Pid int(10);
