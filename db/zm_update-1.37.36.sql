ALTER TABLE Users MODIFY `Username` varchar(64) character set latin1 collate latin1_bin NOT NULL default '';

SELECT 'Checking for Name in Users';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Users'
  AND table_schema = DATABASE()
  AND column_name = 'Name'
  ) > 0,
"SELECT 'Column Name already exists in Users'",
"ALTER TABLE Users ADD `Name` varchar(64) NOT NULL default '' AFTER `Password`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SELECT 'Checking for Email in Users';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Users'
  AND table_schema = DATABASE()
  AND column_name = 'Email'
  ) > 0,
"SELECT 'Column Email already exists in Users'",
"ALTER TABLE Users ADD `Email` varchar(64) NOT NULL default '' AFTER `Name`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SELECT 'Checking for Phone in Users';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Users'
  AND table_schema = DATABASE()
  AND column_name = 'Phone'
  ) > 0,
"SELECT 'Column Phone already exists in Users'",
"ALTER TABLE Users ADD `Phone` varchar(16) NOT NULL default '' AFTER `Email`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
