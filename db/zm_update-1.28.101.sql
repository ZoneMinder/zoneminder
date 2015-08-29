--
-- This updates a 1.28.100 database to 1.28.101
--

--
-- Add Groups column to Users
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'Users'
    AND table_schema = DATABASE()
    AND column_name = 'Groups'
    ) > 0,
"SELECT 'Column Groups exists in Users'",
"ALTER TABLE Users ADD COLUMN `Groups` ENUM('None','View','Edit') NOT NULL DEFAULT 'None' AFTER `Monitors`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

