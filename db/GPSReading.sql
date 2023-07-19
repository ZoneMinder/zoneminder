
DROP TABLE IF EXISTS `GPSReading`;
CREATE TABLE `GPSReading` (
  Id int(10) NOT NULL auto_increment,
  `Latitude`  DECIMAL(8,6),
  `Longitude`  DECIMAL(9,6),
  `Accuracy`  FLOAT,
  `Altitude`  FLOAT,
  `AltitudeAccuracy` FLOAT,
  `Heading` FLOAT,
  `Speed` FLOAT,
  `TimeStamp` TimeStamp,
  `ObjectId`  int(10), 
  `ObjectTypeId` int(10),
  PRIMARY KEY (`Id`)
);

CREATE INDEX GPSReading_Object_idx ON GPSReading (ObjectId, ObjectTypeId);
