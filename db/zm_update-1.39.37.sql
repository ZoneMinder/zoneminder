--
-- This updates a 1.39.36 database to 1.39.37
--
-- Move Remote/rtsp monitors to Ffmpeg.
--
-- The hand-written rtsp client behind Remote/rtsp is removed in this
-- release. Every camera it reached, ffmpeg reaches, so the monitors are
-- converted rather than left to fail. Remote/http is untouched.
--

--
-- Remote/rtsp becomes Ffmpeg with the equivalent url.
--
-- Credentials are deliberately NOT written into the url. FfmpegCamera merges
-- User and Pass into the path itself, percent-encoding them through the Url
-- class, and only when the path does not already carry credentials. Leaving
-- them in their own columns therefore keeps passwords containing @ : / or #
-- working, which string-building them here would break. A Host that already
-- embeds user:pass@ also still works: the path then carries credentials and
-- the runtime merge correctly leaves it alone.
--
UPDATE `Monitors`
   SET `Type` = 'Ffmpeg',
       `Path` = CONCAT('rtsp://', `Host`,
                  IF(`Port` IS NULL OR `Port` = '', '', CONCAT(':', `Port`)),
                  IF(`Path` IS NULL OR `Path` = '', '/',
                     IF(LEFT(`Path`, 1) = '/', `Path`, CONCAT('/', `Path`))))
 WHERE `Type` = 'Remote' AND `Protocol` = 'rtsp';

--
-- Monitors.RTSPDescribe goes with the rtsp client that was its only reader.
--
-- It chose whether to take the media url from the DESCRIBE response rather
-- than the configured path, which was a quirk of ZoneMinder's own rtsp
-- implementation. Ffmpeg handles that itself, so with that client removed the
-- setting had nothing behind it: the web ui still drew the checkbox and still
-- saved the value, but nothing read the column.
--
-- Guarded so the file stays re-runnable: DROP COLUMN is the one statement
-- here that fails rather than no-ops the second time. Scoped to DATABASE()
-- so a same-named column in another schema cannot satisfy the check.
--
set @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE table_schema = DATABASE()
                  AND table_name = 'Monitors' AND column_name = 'RTSPDescribe');
set @sqlstmt := if(@exist > 0,
  'ALTER TABLE `Monitors` DROP COLUMN `RTSPDescribe`',
  "SELECT 'Monitors.RTSPDescribe has already been dropped'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
