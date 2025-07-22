--
-- Update Monitors Table to include Janus_Profile_Override
--

SELECT 'Checking for Janus_Profile_Override in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'Janus_Profile_Override'
  ) > 0,
"SELECT 'Column Janus_Profile_Override already exists in Monitors'",
"ALTER TABLE Monitors ADD Janus_Profile_Override varchar(30) DEFAULT '' AFTER `JanusAudioEnabled`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Update Monitors Table to include Janus_Use_RTSP_Restream
--

SELECT 'Checking for Janus_Use_RTSP_Restream in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'Janus_Use_RTSP_Restream'
  ) > 0,
"SELECT 'Column Janus_Use_RTSP_Restream already exists in Monitors'",
"ALTER TABLE Monitors ADD Janus_Use_RTSP_Restream BOOLEAN NOT NULL DEFAULT false AFTER `Janus_Profile_Override`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
