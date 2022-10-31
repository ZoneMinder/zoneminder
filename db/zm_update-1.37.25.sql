SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Monitor_Status'
     AND column_name = 'UpdatedOn'
    ) > 0,
"ALTER TABLE `Monitor_Status` MODIFY `UpdatedOn` TIMESTAMP NOT NULL default CURRENT_TIMESTAMP",
"ALTER TABLE `Monitor_Status` ADD `UpdatedOn`    TIMESTAMP NOT NULL default CURRENT_TIMESTAMP AFTER CaptureBandwidth"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
