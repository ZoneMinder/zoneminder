--
-- This updates a 1.39.41 database to 1.39.42
--

--
-- Add StreamEncoder to Monitors.
--
-- zmc publishes every monitor's video on its stream socket, but a camera that
-- hands zmc decoded images (Local/V4L2, MJPEG over HTTP, VNC) has no encoded
-- video to forward, so consumers such as zm-api got no live view or snapshot
-- for it.  zmc now encodes those images for the socket, and this column says
-- how: 'mjpeg' (the default, cheap and always available), a hardware encoder
-- such as 'h264_vaapi', or 'none' to publish no video.  Cameras that deliver
-- encoded packets forward them as before and ignore it.
--

SELECT 'Checking for StreamEncoder in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'StreamEncoder'
  ) > 0,
"SELECT 'Column StreamEncoder already exists in Monitors'",
"ALTER TABLE `Monitors` ADD COLUMN `StreamEncoder` varchar(32) NOT NULL default 'mjpeg' AFTER `RTSPStreamName`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
