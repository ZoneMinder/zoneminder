--
-- This updates a 1.27.0 database to 1.27.1
--

--
-- Add Controls definition for Wanscam
--

INSERT INTO Controls VALUES (NULL,'WanscamPT','Remote','Wanscam',1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,16,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,16,0,0,0,0,0,1,16,1,1,1,1,0,0,0,1,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);

-- Add extend alarm frame count to zone definition and Presets
ALTER TABLE `Zones` ADD `ExtendAlarmFrames` smallint(5) unsigned not null default 0 AFTER `OverloadFrames`;
ALTER TABLE `ZonePresets` ADD `ExtendAlarmFrames` smallint(5) unsigned not null default 0 AFTER `OverloadFrames`;

