--
-- This updates a 1.28.108 database to 1.28.109
--

--
-- Update Frame table to have a PrimaryKey of ID, insetad of a Composite Primary Key
-- Used primarially for compatibility with CakePHP
--
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Servers'
	AND table_schema = DATABASE()
	AND column_name = 'Hostname'
	) > 0,
"SELECT 'Column Hostname already exists in Servers'",
"ALTER TABLE `Servers` ADD COLUMN `Hostname` TEXT AFTER Name"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
