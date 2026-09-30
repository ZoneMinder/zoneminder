--
-- Add the AlarmEnd trigger to MonitorActions.
--
-- Alarm fires when motion starts; AlarmEnd fires when the alarm condition is
-- over, which is what a light needs in order to be turned back off.
--
-- This is deliberately not the same as EventEnd. The alarm ends when the
-- monitor leaves the alert state, whereas the event can carry on recording for
-- the rest of its section - on a continuous-recording monitor that can be ten
-- minutes later.
--
-- MODIFY rather than a new column, so the existing rows and their values are
-- untouched; adding a value to the end of an enum does not renumber the ones
-- already stored.
--

SELECT 'Checking for the AlarmEnd trigger in MonitorActions';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'MonitorActions'
  AND table_schema = DATABASE()
  AND column_name = 'TriggerOn'
  AND COLUMN_TYPE LIKE '%AlarmEnd%'
  ) > 0,
"SELECT 'MonitorActions already has the AlarmEnd trigger'",
"ALTER TABLE `MonitorActions` MODIFY `TriggerOn` enum('EventStart','EventEnd','Alarm','AlarmEnd','Manual') NOT NULL default 'EventStart'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
