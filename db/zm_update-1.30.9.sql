--
-- This updates a 1.30.9 database to 1.30.9
--

--
-- Update Monitors table to have an Index on ServerId
--
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.STATISTICS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND index_name = 'Monitors_ServerId_idx'
	) > 0,
"SELECT 'Monitors_ServerId Index already exists on Monitors table'",
"CREATE INDEX `Monitors_ServerId_idx` ON `Monitors` (`ServerId`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;


--
-- Update Server table to have an Index on Name
--
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE table_name = 'Servers'
  AND table_schema = DATABASE()
  AND index_name = 'Servers_Name_idx'
  ) > 0,
"SELECT 'Servers_Name Index already exists on Servers table'",
"CREATE INDEX `Servers_Name_idx` ON `Servers` (`Name`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

