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
