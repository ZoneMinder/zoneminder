--
-- This updates a 1.39.54 database to 1.39.55
--

--
-- Add ONVIF_Event_Tags to Monitors: when set, events raised by the ONVIF event
-- listener are tagged with the objects the camera reports (person, vehicle,
-- package, ...), or with the alarm's topic when it names none.
--

SELECT 'Checking for ONVIF_Event_Tags in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'ONVIF_Event_Tags'
  ) > 0,
"SELECT 'Column ONVIF_Event_Tags already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `ONVIF_Event_Tags` BOOLEAN NOT NULL DEFAULT FALSE AFTER `ONVIF_Event_Listener`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Add Aliases to AI_Object_Classes: what cameras call an object class, so the
-- ONVIF event tags above use the class names object detection does.
--

SELECT 'Checking for Aliases in AI_Object_Classes';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'AI_Object_Classes'
  AND table_schema = DATABASE()
  AND column_name = 'Aliases'
  ) > 0,
"SELECT 'Column Aliases already exists in AI_Object_Classes'",
"ALTER TABLE `AI_Object_Classes` ADD COLUMN `Aliases` TEXT AFTER `Description`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Seed the object classes cameras report themselves (as db/camera_classes.sql).
-- These are starter aliases. Most are Reolink's (PeopleDetect, VehicleDetect,
-- DogCatDetect, FaceDetect, Package, Visitor), of which PeopleDetect, Package
-- and Visitor were seen from a Reolink camera. The rest are from published
-- ONVIF event formats of other cameras (Tapo IsPeople, IsPet, ...; Ajax
-- ClassTypes Human) and are untested. Add the words other cameras use in
-- Options, AI Classes.
--

INSERT IGNORE INTO AI_Datasets (Name, Description, Version, NumClasses) VALUES
('Camera', 'Objects cameras detect themselves, where COCO has no class for them', '1', 5);
SET @camera = (SELECT Id FROM AI_Datasets WHERE Name = 'Camera');

INSERT IGNORE INTO AI_Object_Classes (DatasetId, ClassName, ClassIndex, Description) VALUES
(@camera, 'vehicle', 0, 'Vehicle of any kind'),
(@camera, 'animal', 1, 'Animal of any kind'),
(@camera, 'package', 2, 'Package delivered or picked up'),
(@camera, 'face', 3, 'Face'),
(@camera, 'doorbell', 4, 'Doorbell pressed');

UPDATE AI_Object_Classes c JOIN AI_Datasets d ON c.DatasetId = d.Id
SET c.Aliases = CASE c.ClassName
  WHEN 'person' THEN 'People,PeopleDetect,Human,HumanShapeDetect'
  WHEN 'vehicle' THEN 'VehicleDetect'
  WHEN 'animal' THEN 'Pet,DogCatDetect'
  WHEN 'package' THEN 'PackageDeliver,PackagePickup'
  WHEN 'face' THEN 'FaceDetect'
  WHEN 'doorbell' THEN 'Visitor'
END
WHERE c.Aliases IS NULL
AND ((d.Name = 'COCO' AND c.ClassName = 'person')
  OR (d.Name = 'Camera' AND c.ClassName IN ('vehicle', 'animal', 'package', 'face', 'doorbell')));
