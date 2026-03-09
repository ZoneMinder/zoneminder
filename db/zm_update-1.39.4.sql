--
-- Add Quadra menu item for existing installs
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM `Menu_Items` WHERE `MenuKey` = 'Quadra') > 0,
"SELECT 'Quadra menu item already exists'",
"INSERT INTO `Menu_Items` (`MenuKey`, `Enabled`, `SortOrder`) VALUES ('Quadra', 1, 85)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
