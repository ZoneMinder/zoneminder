--
-- This updates a 1.28.109 database to 1.28.110
--

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Logs'
	AND table_schema = DATABASE()
	AND column_name = 'ServerId'
	) > 0,
"SELECT 'Column ServerId already exists in Logs'",
"ALTER TABLE `Logs` ADD COLUMN `ServerId`  int(10) unsigned AFTER Component"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
