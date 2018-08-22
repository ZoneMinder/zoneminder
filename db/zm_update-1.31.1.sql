--
-- This updates a 1.30.0 database to 1.30.1
--
-- Add StateId Column to Events.
--
SELECT 'Checkfor StateId IN Events';

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Events'
    AND table_schema = DATABASE()
    AND column_name = 'StateId'
    ) > 0,
"SELECT 'Column StateId exists in Events'",
"ALTER TABLE Events ADD `StateId` int(10) unsigned default NULL AFTER `Notes`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

