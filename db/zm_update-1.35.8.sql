/* The MEMORY TABLE TYPE IS BAD! Switch to regular InnoDB */

DROP TABLE IF EXISTS `Monitor_Status`;
CREATE TABLE `Monitor_Status` (
  `MonitorId` int(10) unsigned NOT NULL,
  `Status`  enum('Unknown','NotRunning','Running','Connected','Signal') NOT NULL default 'Unknown',
  `CaptureFPS`  DECIMAL(10,2) NOT NULL default 0,
  `AnalysisFPS`  DECIMAL(5,2) NOT NULL default 0,
  `CaptureBandwidth`  INT NOT NULL default 0,
  PRIMARY KEY (`MonitorId`)
) ENGINE=InnoDB;

