--
-- This updates a 1.26.5 database to 1.27
--

--
-- Add Libvlc and cURL monitor types
--

ALTER TABLE Controls modify column Type enum('Local','Remote','Ffmpeg','Libvlc','cURL') NOT NULL default 'Local';
ALTER TABLE MonitorPresets modify column Type enum('Local','Remote','File','Ffmpeg','Libvlc','cURL') NOT NULL default 'Local';
ALTER TABLE Monitors modify column Type enum('Local','Remote','File','Ffmpeg','Libvlc','cURL') NOT NULL default 'Local';

--
-- Add required fields for cURL authenication
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'User'
    ) > 0,
"SELECT 'Column User exists in Monitors'",
"ALTER TABLE `Monitors` ADD `User` VARCHAR(32) NOT NULL AFTER `SubPath`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'Pass'
    ) > 0,
"SELECT 'Column Pass exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Pass` VARCHAR(32) NOT NULL AFTER `User`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add default zone preset
--

INSERT INTO ZonePresets (Name,Type,Units,CheckMethod, MinPixelThreshold, MaxPixelThreshold, MinAlarmPixels, MaxAlarmPixels, FilterX, FilterY, MinFilterPixels, MaxFilterPixels, MinBlobPixels, MaxBlobPixels, MinBlobs, MaxBlobs, OverloadFrames ) VALUES ('Default','Active','Percent','Blobs',25,NULL,3,75,3,3,3,75,2,NULL,1,NULL,0);
