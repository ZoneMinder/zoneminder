--
-- This updates a 1.27.0 database to 1.27.1
--

--
-- Add Controls definition for Wanscam
--

INSERT INTO Controls VALUES (NULL,'WanscamPT','Remote','Wanscam',1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,16,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,16,0,0,0,0,0,1,16,1,1,1,1,0,0,0,1,1,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0,0,0);

--
-- Add MotionSkipFrame field for controlling how many frames motion detection should skip.
--

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'MotionFrameSkip'
	) > 0,
"SELECT 1",
"ALTER TABLE `Monitors` ADD `MotionFrameSkip` smallint(5) unsigned NOT NULL default '0' AFTER `FrameSkip`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;