

SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE table_name = 'Monitor_Status'
  AND table_schema = DATABASE()
  AND index_name = 'Monitor_Status_UpdatedOn_idx'
  ) > 0,
"SELECT 'UpdateOn Index already exists on Monitor_Status table'",
"CREATE INDEX Monitor_Status_UpdatedOn_idx on Monitor_Status(UpdatedOn)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Rename Janus-specific restream fields to be more generic
-- These fields are now used by Go2RTC and RTSP2Web as well
--

SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'Janus_Use_RTSP_Restream'
  ) > 0,
"ALTER TABLE Monitors CHANGE `Janus_Use_RTSP_Restream` `Restream` BOOLEAN NOT NULL DEFAULT false",
"SELECT 'Restream column already exists or Janus_Use_RTSP_Restream not found'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'Janus_RTSP_User'
  ) > 0,
"ALTER TABLE Monitors CHANGE `Janus_RTSP_User` `RTSP_User` INT(10)",
"SELECT 'RTSP_User column already exists or Janus_RTSP_User not found'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
