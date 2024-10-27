SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'UpdatedOn'
    ) > 0,
"SELECT 'Column UpdatedOn already exists in Monitor_Status'",
"ALTER TABLE `Monitor_Status` ADD `UpdatedOn`     datetime AFTER CaptureBandwidth"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
