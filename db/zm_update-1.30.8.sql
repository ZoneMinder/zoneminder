--
-- This updates a 1.30.7 database to 1.30.8
--

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Events'
	AND table_schema = DATABASE()
	AND column_name = 'Orientation'
	) > 0,
"SELECT 'Column Orientation exists in Events'",
"ALTER TABLE `Events` ADD `Orientation`  enum('0','90','180','270','hori','vert') NOT NULL default '0' AFTER Notes",
));

PREPARE stmt FROM @s;
EXECUTE stmt;
