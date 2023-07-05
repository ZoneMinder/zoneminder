--
-- Drop Foreign Keys on Events table.
--

SELECT 'Checking for FOREIGN KEYS on Events Table';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  WHERE TABLE_NAME = 'Frames'
  AND REFERENCED_TABLE_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME = 'Events'
  AND CONSTRAINT_NAME = 'Frames_ibfk_1'
  ) > 0,
"ALTER TABLE Frames DROP FOREIGN KEY Frames_ibfk_1",
"SELECT 'FOREIGN KEY In Frames table already removed or has a different name.'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  WHERE TABLE_NAME = 'Stats'
  AND REFERENCED_tABLE_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME = 'Events'
  AND CONSTRAINT_NAME = 'Stats_ibfk_1'
  ) > 0,
"ALTER TABLE Stats DROP FOREIGN KEY Stats_ibfk_1",
"SELECT 'FOREIGN KEY In Stats table already removed or has a different name.'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  WHERE TABLE_NAME = 'Stats'
  AND REFERENCED_tABLE_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME = 'Events'
  AND CONSTRAINT_NAME = 'Stats_ibfk_2'
  ) > 0,
"ALTER TABLE Stats DROP FOREIGN KEY Stats_ibfk_2",
"SELECT 'FOREIGN KEY In Stats table already removed or has a different name.'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  WHERE TABLE_NAME = 'Stats'
  AND REFERENCED_tABLE_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME = 'Events'
  AND CONSTRAINT_NAME = 'Stats_ibfk_3'
  ) > 0,
"ALTER TABLE Stats DROP FOREIGN KEY Stats_ibfk_3",
"SELECT 'FOREIGN KEY In Stats table already removed or has a different name.'"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
  WHERE TABLE_NAME = 'Stats'
  AND REFERENCED_tABLE_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME = 'Events'
  AND CONSTRAINT_NAME = 'Stats_ibfk_4'
  ) > 0,
"ALTER TABLE Stats DROP FOREIGN KEY Stats_ibfk_4",
"SELECT 'FOREIGN KEY In Stats table already removed or has a different name.'"
));
