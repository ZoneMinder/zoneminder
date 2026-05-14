--
-- Recalibrate stock ZonePresets for modern HD resolutions.
--
-- The legacy values (MinAlarmPixels 3-36% of zone area) were authored for
-- ~320x240 analog cameras. At 1080p they require a 322x322-px motion blob
-- to trigger "Fast, high sensitivity" — far too coarse. New scale: low=3%,
-- medium~0.5%, high=0.1%. MaxPixelThreshold (grayscale 0-255) is unchanged.
--
-- Only updates the 7 stock preset Ids; any user-added presets (Id > 7)
-- are left untouched.

UPDATE ZonePresets SET MinAlarmPixels=0.5,  MaxAlarmPixels=75,   MinFilterPixels=0.35, MaxFilterPixels=75, MinBlobPixels=0.3  WHERE Id=1;
UPDATE ZonePresets SET MinAlarmPixels=3                                                                                       WHERE Id=2;
UPDATE ZonePresets SET MinAlarmPixels=0.5                                                                                     WHERE Id=3;
UPDATE ZonePresets SET MinAlarmPixels=0.1                                                                                     WHERE Id=4;
UPDATE ZonePresets SET MinAlarmPixels=5,                          MinFilterPixels=3.5,                      MinBlobPixels=3    WHERE Id=5;
UPDATE ZonePresets SET MinAlarmPixels=1,                          MinFilterPixels=0.7,                      MinBlobPixels=0.6  WHERE Id=6;
UPDATE ZonePresets SET MinAlarmPixels=0.2,                        MinFilterPixels=0.14,                     MinBlobPixels=0.12 WHERE Id=7;

--
-- Add an index on Sessions.access to support session garbage collection.
--

SET @s = (SELECT IF(
  (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_name = 'Sessions'
    AND table_schema = DATABASE()
    AND index_name = 'Sessions_access_idx'
  ) > 0,
  "SELECT 'access Index already exists on Sessions table'",
  "CREATE INDEX Sessions_access_idx ON Sessions (`access`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
