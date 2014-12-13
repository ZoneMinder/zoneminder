ALTER TABLE Monitors MODIFY Device tinytext;

CREATE TABLE `PluginsConfig` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(32) NOT NULL DEFAULT '',
  `Value` text NOT NULL,
  `Type` tinytext NOT NULL,
  `Choices` text default NULL,
  `Min` int(10) unsigned NULL default NULL,
  `Max` int(10) unsigned NULL default NULL,
  `MonitorId` int(10) unsigned NOT NULL,
  `ZoneId` int(10) unsigned NOT NULL,
  `pluginName` varchar(255) NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `ZoneId` (`ZoneId`),
  KEY `MonitorId` (`MonitorId`),
  KEY `Name` (`Name`),
  KEY `pluginName` (`pluginName`)
) ENGINE=InnoDB;

ALTER TABLE `Monitors` ADD `DoNativeMotDet` tinyint(3) unsigned NOT NULL default '1' AFTER `Sequence`;
