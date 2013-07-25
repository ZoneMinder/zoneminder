--
-- Table structure for table `PluginsConfig`
--

CREATE TABLE IF NOT EXISTS `PluginsConfig` (
  `Id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(32) NOT NULL DEFAULT '',
  `Value` text NOT NULL,
  `Type` tinytext NOT NULL,
  `Choices` text NOT NULL,
  `MonitorId` int(10) unsigned NOT NULL,
  `ZoneId` int(10) unsigned NOT NULL,
  `pluginName` tinytext NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `ZoneId` (`ZoneId`),
  KEY `MonitorId` (`MonitorId`),
  KEY `Name` (`Name`),
  FULLTEXT KEY `pluginName` (`pluginName`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;


ALTER TABLE `Monitors` ADD `UsedPl` varchar(88) NOT NULL default '' AFTER `Sequence`;
ALTER TABLE `Monitors` ADD `DoNativeMotDet` varchar(5) NOT NULL default 'yes' AFTER `UsedPl`;
ALTER TABLE `Monitors` ADD `Colours` TINYINT UNSIGNED NOT NULL DEFAULT '1' AFTER `Height`;
ALTER TABLE `Monitors` ADD `Deinterlacing` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `Orientation`;

