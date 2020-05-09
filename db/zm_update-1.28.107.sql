--
-- This updates a 1.28.106 database to 1.28.107
--

--
-- Update Frame table to have a PrimaryKey of ID, insetad of a Composite Primary Key
-- Used primarially for compatibility with CakePHP
--
SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Frames'
	AND table_schema = DATABASE()
	AND column_name = 'Id'
	) > 0,
"SELECT 'Column ID already exists in Frames'",
"ALTER TABLE `Frames` ADD COLUMN `Id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT FIRST, DROP PRIMARY KEY, ADD PRIMARY KEY(`Id`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
