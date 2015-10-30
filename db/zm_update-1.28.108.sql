--
-- This updates a 1.28.107 database to 1.28.108
--

--
-- Update Frame table to have an Index on EventId, per the change made in 1.28.107
--
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.STATISTICS
	WHERE table_name = 'Frames'
	AND table_schema = DATABASE()
	AND index_name = 'EventId_idx'
	) > 0,
"SELECT 'EventId Index already exists on Frames table'",
"CREATE INDEX `EventId_idx` ON `Frames` (`EventId`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;


