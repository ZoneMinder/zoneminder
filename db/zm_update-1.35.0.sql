SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'EmailTo'
    ) > 0,
"SELECT 'Column EmailTo already exists in Filters'",
"ALTER TABLE `Filters` ADD `EmailTo` TEXT AFTER `AutoEmail`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

UPDATE Filters SET EmailTo=(SELECT Value FROM Config WHERE Name='ZM_EMAIL_ADDRESS') WHERE AutoEmail=1;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'EmailSubject'
    ) > 0,
"SELECT 'Column EmailSubject already exists in Filters'",
"ALTER TABLE `Filters` ADD `EmailSubject` TEXT AFTER `EmailTo`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

UPDATE Filters SET EmailSubject=(SELECT Value FROM Config WHERE Name='ZM_EMAIL_SUBJECT') WHERE AutoEmail=1;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Filters'
     AND column_name = 'EmailBody'
    ) > 0,
"SELECT 'Column EmailBody already exists in Filters'",
"ALTER TABLE `Filters` ADD `EmailBody` TEXT AFTER `EmailSubject`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

UPDATE Filters SET EmailBody=(SELECT Value FROM Config WHERE Name='ZM_EMAIL_BODY') WHERE AutoEmail=1;
