--
-- This updates a 1.30.1 database to 1.30.2
--

ALTER TABLE Users MODIFY MonitorIds TEXT NOT NULL;
