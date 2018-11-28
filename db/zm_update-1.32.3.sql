--
-- This updates a 1.32.2 database to 1.32.3
--

--
-- Add Protocol column to Storage
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'Protocol'
    ) > 0,
"SELECT 'Column Protocol already exists in Servers'",
"ALTER TABLE Servers ADD `Protocol` TEXT AFTER `Id`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add PathToIndex column to Storage
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'PathToIndex'
    ) > 0,
"SELECT 'Column PathToIndex already exists in Servers'",
"ALTER TABLE Servers ADD `PathToIndex` TEXT AFTER `Hostname`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add PathToZMS column to Storage
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'PathToZMS'
    ) > 0,
"SELECT 'Column PathToZMS already exists in Servers'",
"ALTER TABLE Servers ADD `PathToZMS` TEXT AFTER `PathToIndex`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Add Port column to Storage
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'Port'
    ) > 0,
"SELECT 'Column Port already exists in Servers'",
"ALTER TABLE Servers ADD `Port` INTEGER UNSIGNED AFTER `Hostname`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
