--
-- This updates a 1.39.41 database to 1.39.42
--
-- Let a monitor action run at a time of day as well as on an event.
--
-- MonitorActions already says what to do (LightOn, AudioPlay, ...) and to
-- which device; all that was missing was "at 22:30" alongside the existing
-- EventStart/Alarm/Manual triggers. So this adds a trigger rather than a
-- table: a scheduled action is an ordinary action whose TriggerOn is
-- 'Schedule' and whose Schedule column holds a five field cron spec.
--
-- The spec is written into the web user's own crontab, which runs
-- zmcontrol.pl exactly as the web ui does for a manual action. Nothing
-- validates it on the way in from the database, so whatever writes here must
-- have passed it through cronScheduleIsValid() first -- see
-- web/includes/CronTab.php.
--

SELECT 'Checking for Schedule in MonitorActions';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'MonitorActions'
  AND table_schema = DATABASE()
  AND column_name = 'Schedule'
  ) > 0,
"SELECT 'Column Schedule already exists in MonitorActions'",
"ALTER TABLE `MonitorActions` ADD COLUMN `Schedule` varchar(64) NOT NULL default '' AFTER `TriggerOn`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Checking for Schedule in the MonitorActions.TriggerOn enum';
SET @s = (SELECT IF(
  (SELECT LOCATE('Schedule', COLUMN_TYPE)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'MonitorActions'
  AND table_schema = DATABASE()
  AND column_name = 'TriggerOn'
  ) > 0,
"SELECT 'TriggerOn already offers Schedule'",
"ALTER TABLE `MonitorActions` MODIFY `TriggerOn` enum('EventStart','EventEnd','Alarm','AlarmEnd','Manual','Schedule') NOT NULL default 'EventStart'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
