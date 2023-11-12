UPDATE Monitors SET ControlAddress='' WHERE ControlAddress='user:port@ip';

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
 ADD COLUMN  `FloorplanID` INT default NULL AFTER `FloorplanY`,
 ADD COLUMN  `FloorplanPoint` SMALLINT NOT NULL default '0' AFTER `FloorplanID`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
