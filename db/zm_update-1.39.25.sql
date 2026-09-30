--
-- Bring columns that upgraded installs never had reconciled back in line with
-- zm_create.sql.in, so an upgraded schema matches a fresh one.
--

UPDATE `Monitors` SET `Janus_Profile_Override` = '' WHERE `Janus_Profile_Override` IS NULL;
ALTER TABLE `Monitors` MODIFY `Janus_Profile_Override` VARCHAR(30) NOT NULL DEFAULT '';

UPDATE `Monitors` SET `Janus_RTSP_Session_Timeout` = 0 WHERE `Janus_RTSP_Session_Timeout` IS NULL;
ALTER TABLE `Monitors` MODIFY `Janus_RTSP_Session_Timeout` INT(10) NOT NULL DEFAULT '0';

ALTER TABLE `Monitors` MODIFY `MQTT_Subscriptions` varchar(255) default '';
ALTER TABLE `Monitors` MODIFY `OutputCodecName` varchar(32) NOT NULL default 'auto';
ALTER TABLE `Monitors` MODIFY `OutputContainer` enum('auto','mp4','mkv','webm');

ALTER TABLE `ZonePresets` MODIFY `Units` enum('Pixels','Percent') NOT NULL default 'Percent';
