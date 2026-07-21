--
-- Add Restream column (renamed from Janus_Use_RTSP_Restream)
-- Keep the old column for backwards compatibility with older versions
--

--
-- Add new Restream column if it doesn't exist
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'Restream'
    ) > 0,
"SELECT 'Column Restream already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Restream` BOOLEAN NOT NULL DEFAULT false AFTER `Janus_Profile_Override`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Copy data from Janus_Use_RTSP_Restream to Restream if old column exists
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'Janus_Use_RTSP_Restream'
    ) > 0,
"UPDATE `Monitors` SET `Restream` = `Janus_Use_RTSP_Restream`",
"SELECT 'Column Janus_Use_RTSP_Restream does not exist, skipping data migration'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Add new RTSP_User column if it doesn't exist (renamed from Janus_RTSP_User)
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'RTSP_User'
    ) > 0,
"SELECT 'Column RTSP_User already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `RTSP_User` INT(10) AFTER `Restream`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Copy data from Janus_RTSP_User to RTSP_User if old column exists
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'Janus_RTSP_User'
    ) > 0,
"UPDATE `Monitors` SET `RTSP_User` = `Janus_RTSP_User`",
"SELECT 'Column Janus_RTSP_User does not exist, skipping data migration'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Note: We intentionally keep Janus_Use_RTSP_Restream and Janus_RTSP_User
-- columns to allow reverting to older versions. They can be removed in a
-- future major release.
--
