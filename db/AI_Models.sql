--
-- Table structure for AI_Models
--

CREATE TABLE `AI_Models` (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `Name` varchar(64) NOT NULL,
  `Description` TEXT,
  `ModelPath` varchar(255),
  `Framework` enum('TensorFlow','PyTorch','ONNX','Other') NOT NULL default 'ONNX',
  `Version` varchar(32),
  PRIMARY KEY (`Id`),
  UNIQUE KEY `AI_Models_Name_idx` (`Name`)
) ENGINE=@ZM_MYSQL_ENGINE@;

--
-- Table structure for AI_Object_Classes
--

CREATE TABLE `AI_Object_Classes` (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `ModelId` int(10) unsigned NOT NULL,
  `ClassName` varchar(64) NOT NULL,
  `ClassIndex` int(10) unsigned NOT NULL,
  `Description` TEXT,
  PRIMARY KEY (`Id`),
  KEY `AI_Object_Classes_ModelId_idx` (`ModelId`),
  UNIQUE KEY `AI_Object_Classes_Model_Class_idx` (`ModelId`, `ClassName`),
  FOREIGN KEY (`ModelId`) REFERENCES `AI_Models` (`Id`) ON DELETE CASCADE
) ENGINE=@ZM_MYSQL_ENGINE@;

--
-- Table structure for AI_Detection_Settings
--

CREATE TABLE `AI_Detection_Settings` (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `MonitorId` int(10) unsigned NULL,
  `ObjectClassId` int(10) unsigned NOT NULL,
  `Enabled` tinyint(1) unsigned NOT NULL default 1,
  `ReportDetection` tinyint(1) unsigned NOT NULL default 1,
  `ConfidenceThreshold` tinyint(3) unsigned NOT NULL default 50,
  `BoxColor` varchar(7) NOT NULL default '#FF0000',
  PRIMARY KEY (`Id`),
  KEY `AI_Detection_Settings_MonitorId_idx` (`MonitorId`),
  KEY `AI_Detection_Settings_ObjectClassId_idx` (`ObjectClassId`),
  UNIQUE KEY `AI_Detection_Settings_Monitor_Object_idx` (`MonitorId`, `ObjectClassId`),
  FOREIGN KEY (`MonitorId`) REFERENCES `Monitors` (`Id`) ON DELETE CASCADE,
  FOREIGN KEY (`ObjectClassId`) REFERENCES `AI_Object_Classes` (`Id`) ON DELETE CASCADE
) ENGINE=@ZM_MYSQL_ENGINE@;

--
-- Table structure for AI_Detections
--

CREATE TABLE `AI_Detections` (
  `Id` BIGINT unsigned NOT NULL auto_increment,
  `EventId` BIGINT unsigned NOT NULL,
  `FrameId` int(10) unsigned,
  `ObjectClassId` int(10) unsigned NOT NULL,
  `Confidence` decimal(5,4) NOT NULL,
  `BoundingBoxX` int(10) unsigned,
  `BoundingBoxY` int(10) unsigned,
  `BoundingBoxWidth` int(10) unsigned,
  `BoundingBoxHeight` int(10) unsigned,
  `DetectedAt` TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`Id`),
  KEY `AI_Detections_EventId_idx` (`EventId`),
  KEY `AI_Detections_FrameId_idx` (`FrameId`),
  KEY `AI_Detections_ObjectClassId_idx` (`ObjectClassId`),
  FOREIGN KEY (`EventId`) REFERENCES `Events` (`Id`) ON DELETE CASCADE,
  FOREIGN KEY (`FrameId`) REFERENCES `Frames` (`FrameId`) ON DELETE SET NULL,
  FOREIGN KEY (`ObjectClassId`) REFERENCES `AI_Object_Classes` (`Id`) ON DELETE CASCADE
) ENGINE=@ZM_MYSQL_ENGINE@;
