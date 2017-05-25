--
-- This updates a 1.28.103 database to 1.28.104
--

--
-- Add AnalysisFPS column to Monitors
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'AnalysisFPS'
    ) > 0,
"SELECT 'Column AnalysisFPS exists in Monitors'",
"ALTER TABLE Monitors ADD `AnalysisFPS` decimal(5,2) default NULL AFTER `MotionFrameSkip`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add AnalysisUpdateDelay column to Monitors
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Monitors'
    AND table_schema = DATABASE()
    AND column_name = 'AnalysisUpdateDelay'
    ) > 0,
"SELECT 'Column AnalysisUpdateDelay exists in Monitors'",
"ALTER TABLE Monitors ADD `AnalysisUpdateDelay` smallint(5) unsigned not null default 0 AFTER `AnalysisFPS`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

