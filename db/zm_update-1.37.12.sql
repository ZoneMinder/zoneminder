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

UPDATE `Monitors` SET `Capturing` = 'None' WHERE `Function` = 'None';
UPDATE `Monitors` SET `Capturing` = 'Always' WHERE `Function` != 'None';

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

UPDATE `Monitors` SET `Analysing` = 'None' WHERE `Function` = 'Record' OR `Function` = 'Nodect' OR `Function` = 'None';
UPDATE `Monitors` SET `Analysing` = 'Always' WHERE `Function` = 'Modect' OR `Function` = 'Mocord';

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

UPDATE `Monitors` SET `Recording`='None', `Analysing`='None' WHERE `Function` = 'Monitor';
UPDATE `Monitors` SET `Recording`='OnMotion', `Analysing`='None' WHERE `Function` = 'Nodect';
UPDATE `Monitors` SET `Recording`='OnMotion' WHERE `Function` = 'Modect';
UPDATE `Monitors` SET `Recording`='Always' WHERE `Function` = 'Mocord';

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
