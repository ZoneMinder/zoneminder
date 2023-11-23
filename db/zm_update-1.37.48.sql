UPDATE Monitors SET ControlAddress='' WHERE ControlAddress='user:port@ip';

--
-- This adds Floorplans Table
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Floorplans'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Floorplans table exists'",
    "CREATE TABLE Floorplans (
  id INT(10) unsigned NOT NULL AUTO_INCREMENT,
  url text,
  name text,
  PRIMARY KEY(id)
) ENGINE=InnoDB;"
  ));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- Update Monitors table to have Floorplan entries
--

SELECT 'Checking for Floorplan in Monitors';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Monitors'
  AND table_schema = DATABASE()
  AND column_name = 'FloorplanX'
  ) > 0,
"SELECT 'Floorplan columns already exist on Monitors'",
 "ALTER TABLE `Monitors`
 ADD COLUMN  `FloorplanX` INT NOT NULL default '0' AFTER `Longitude`,
 ADD COLUMN  `FloorplanY` INT NOT NULL default '0' AFTER `FloorplanX`,
 ADD COLUMN  `FloorplanID` INT(10) default NULL AFTER `FloorplanY`,
 ADD COLUMN  `FloorplanPoint` SMALLINT NOT NULL default '0' AFTER `FloorplanID`,
 ADD FORIEGN KEY `FloorplanID` REFERNCES Floorplans(`id`) ON DELETE SET NULL"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
