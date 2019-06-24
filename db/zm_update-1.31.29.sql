DROP TABLE IF EXISTS `Monitor_Status`;
CREATE TABLE `Monitor_Status` (
  `MonitorId` int(10) unsigned NOT NULL,
  `Status`  enum('Unknown','NotRunning','Running','NoSignal','Signal') NOT NULL default 'Unknown',
  `CaptureFPS`  DECIMAL(10,2) NOT NULL default 0,
  `AnalysisFPS`  DECIMAL(5,2) NOT NULL default 0,
  PRIMARY KEY (`MonitorId`)
) ENGINE=MEMORY;
