--
-- Add AI Models tables for ZoneMinder
--

-- AI Datasets table - stores datasets like COCO, ImageNet, etc.
CREATE TABLE IF NOT EXISTS `AI_Datasets` (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `Name` varchar(64) NOT NULL,
  `Description` TEXT,
  `Version` varchar(32),
  `NumClasses` int(10) unsigned NOT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `AI_Datasets_Name_idx` (`Name`)
) ENGINE=InnoDB;

-- AI Models table - stores AI model implementations
CREATE TABLE IF NOT EXISTS `AI_Models` (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `Name` varchar(64) NOT NULL,
  `Description` TEXT,
  `ModelPath` varchar(255),
  `Framework` enum('TensorFlow','PyTorch','ONNX','OpenVINO','TensorRT','Other') NOT NULL default 'ONNX',
  `Version` varchar(32),
  `DatasetId` int(10) unsigned,
  `Enabled` tinyint(1) unsigned NOT NULL default 0,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `AI_Models_Name_idx` (`Name`),
  FOREIGN KEY (`DatasetId`) REFERENCES `AI_Datasets` (`Id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- AI Object Classes table - stores object classes from datasets
CREATE TABLE IF NOT EXISTS `AI_Object_Classes` (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `DatasetId` int(10) unsigned NOT NULL,
  `ClassName` varchar(64) NOT NULL,
  `ClassIndex` int(10) unsigned NOT NULL,
  `Description` TEXT,
  PRIMARY KEY (`Id`),
  KEY `AI_Object_Classes_DatasetId_idx` (`DatasetId`),
  UNIQUE KEY `AI_Object_Classes_Dataset_Class_idx` (`DatasetId`, `ClassName`),
  FOREIGN KEY (`DatasetId`) REFERENCES `AI_Datasets` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- AI Detection Settings table - stores detection settings per monitor and object class
CREATE TABLE IF NOT EXISTS `AI_Detection_Settings` (
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
) ENGINE=InnoDB;

-- AI Detections table - stores actual detection results
CREATE TABLE IF NOT EXISTS `AI_Detections` (
  `Id` BIGINT unsigned NOT NULL auto_increment,
  `EventId` BIGINT unsigned NOT NULL,
  `FrameId` BIGINT unsigned,
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
  FOREIGN KEY (`FrameId`) REFERENCES `Frames` (`Id`) ON DELETE SET NULL,
  FOREIGN KEY (`ObjectClassId`) REFERENCES `AI_Object_Classes` (`Id`) ON DELETE CASCADE
) ENGINE=InnoDB;
