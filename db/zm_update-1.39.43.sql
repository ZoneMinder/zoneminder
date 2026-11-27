--
-- This updates a 1.39.42 database to 1.39.43
--
-- Add CronJobs: arbitrary commands on a schedule, which is the rest of what
-- issue #4957 asked for.
--
-- No new daemon. ZoneMinder already rewrites a marked block in the web user's
-- crontab for scheduled monitor actions (1.39.42), leaving every line outside
-- that block alone; these rows go into the same block. cron does the timing,
-- the supervision and the running, all of which it has been doing since 1975.
--
-- Command is written verbatim into a crontab line and runs as the web user,
-- so editing these needs System edit rights -- the same trust Filters'
-- AutoExecuteCmd has always carried. Validate with cronCommandIsValid()
-- before writing here: nothing between this table and the crontab checks it
-- again.
--
-- ServerId NULL means every server writes the job into its own crontab, which
-- is what a single server install wants and gets by default. Naming a server
-- restricts it to that one.
--

SELECT 'Checking for CronJobs table';
CREATE TABLE IF NOT EXISTS `CronJobs` (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `Name` varchar(64) NOT NULL default '',
  `Enabled` tinyint(1) unsigned NOT NULL default '1',
  `Schedule` varchar(64) NOT NULL default '',
  `Command` text NOT NULL,
  `ServerId` int(10) unsigned default NULL,
  PRIMARY KEY (`Id`),
  KEY `Enabled` (`Enabled`)
) ENGINE=InnoDB;
