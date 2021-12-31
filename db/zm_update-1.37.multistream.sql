SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'Capturing'
    ) > 0,
"SELECT 'Column Capturing already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Capturing` enum('None','Ondemand', 'Always') NOT NULL default 'Always' AFTER `Function`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'Analysing'
    ) > 0,
"SELECT 'Column Analysing already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Analysing` enum('None','Always') NOT NULL default 'Always'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'AnalysisSource'
    ) > 0,
"SELECT 'Column AnalysisSource already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `AnalysisSource` enum('Primary','Secondary') NOT NULL DEFAULT 'Primary' AFTER `Analysing`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'Recording'
    ) > 0,
"SELECT 'Column Recording already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `Recording` enum('None', 'OnMotion', 'Always') NOT NULL default 'Always'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'RecordingSource'
    ) > 0,
"SELECT 'Column RecordingSource already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `RecordingSource` enum('Primary','Secondary','Both') NOT NULL DEFAULT 'Primary' AFTER `RecordAudio`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
