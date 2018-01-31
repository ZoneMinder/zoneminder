DROP TABLE IF EXISTS `Monitor_Status`;
CREATE TABLE `Monitor_Status` (
  `MonitorId` int(10) unsigned NOT NULL,
  `Status`  enum('Unknown','NotRunning','Running','Connected','Signal') NOT NULL default 'Unknown',
  `CaptureFPS`  DECIMAL(10,2) NOT NULL default 0,
  `AnalysisFPS`  DECIMAL(5,2) NOT NULL default 0,
  PRIMARY KEY (`MonitorId`)
) ENGINE=MEMORY;

SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO';

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM Storage WHERE Name = 'Default' AND Id=0 AND Path='/var/cache/zoneminder/events'
    ) > 0,
    "SELECT 'Default Storage Area already exists.'",
    "INSERT INTO Storage (Id,Name,Path,Scheme,ServerId) VALUES (0,'Default','/var/cache/zoneminder/events','Medium',NULL)"
    ));

PREPARE stmt FROM @s;
EXECUTE stmt;
