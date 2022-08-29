SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitors'
     AND column_name = 'AnalysisImage'
    ) > 0,
"SELECT 'Column AnalysisImage already exists in Monitors'",
"ALTER TABLE `Monitors` ADD `AnalysisImage` enum('FullColour','YChannel') NOT NULL DEFAULT 'FullColour' AFTER `AnalysisSource`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
