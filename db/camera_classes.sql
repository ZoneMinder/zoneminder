--
-- Object classes that cameras detect themselves and report over ONVIF, and
-- what they call them. An ONVIF alarm naming one of these words is tagged
-- with the class (Monitors.ONVIF_Event_Tags). Classes COCO has are given
-- aliases there; the rest, which COCO has no class for, are the Camera dataset.
-- Aliases that are already set are kept.
--
-- These are starter aliases. Most are Reolink's (PeopleDetect, VehicleDetect,
-- DogCatDetect, FaceDetect, Package, Visitor), of which PeopleDetect, Package
-- and Visitor were seen from a Reolink camera. The rest are from published
-- ONVIF event formats of other cameras (Tapo IsPeople, IsPet, ...; Ajax
-- ClassTypes Human) and are untested. Add the words other cameras use in
-- Options, AI Classes.
--
-- zm_update-1.39.57.sql also runs this file, so it must keep working on a
-- 1.39.57 database: a change that needs a later column goes in a later
-- zm_update file, not here.
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
