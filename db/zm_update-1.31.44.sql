
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'CaptureBandwidth'
    ) > 0,
"SELECT 'Column CaptureBandwidth already exists in Monitor_Status'",
"ALTER TABLE `Monitor_Status` ADD `CaptureBandwidth`  INT NOT NULL default 0 AFTER `AnalysisFPS`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
