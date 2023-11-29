UPDATE Monitors SET ControlAddress='' WHERE ControlAddress='user:port@ip';

DROP TRIGGER IF EXISTS Events_Hour_delete_trigger;
DROP TRIGGER IF EXISTS Events_Hour_update_trigger;
DROP TRIGGER IF EXISTS Events_Day_delete_trigger;
DROP TRIGGER IF EXISTS Events_Day_update_trigger;
DROP TRIGGER IF EXISTS Events_Week_delete_trigger;
DROP TRIGGER IF EXISTS Events_Week_update_trigger;
DROP TRIGGER IF EXISTS Events_Month_delete_trigger;
DROP TRIGGER IF EXISTS Events_Month_update_trigger;
DROP TRIGGER IF EXISTS event_insert_trigger;
DROP TRIGGER IF EXISTS event_update_trigger;
DROP TRIGGER IF EXISTS event_delete_trigger;

DROP TABLE IF EXISTS `Events_Hour`;
DROP TABLE IF EXISTS `Events_Day`;
DROP TABLE IF EXISTS `Events_Week`;
DROP TABLE IF EXISTS `Events_Month`;
DROP TABLE IF EXISTS `Events_Archived`;
