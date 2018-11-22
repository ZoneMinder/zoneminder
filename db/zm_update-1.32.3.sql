--
-- This updates a 1.32.2 database to 1.32.3
--

--
-- Add some additional monitor preset values
--

INSERT INTO MonitorPresets VALUES (NULL,'D-link DCS-930L, 640x480, mjpeg','Remote','http',0,0,'http','simple','<ip-address>',80,'/mjpeg.cgi',NULL,640,480,3,NULL,0,NULL,NULL,NULL,100,100);

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
-- Add Prefix column to Storage
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Servers'
     AND column_name = 'PathPrefix'
    ) > 0,
"SELECT 'Column PathPrefix already exists in Servers'",
"ALTER TABLE Servers ADD `PathPrefix` TEXT AFTER `Hostname`"
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
