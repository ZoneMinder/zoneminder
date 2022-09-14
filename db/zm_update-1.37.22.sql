--
-- This adds the Reports Table
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Reports'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Reports table exists'",
    "
CREATE TABLE Reports (
  Id INT(10) UNSIGNED auto_increment,
  Name varchar(30),
  FilterId int(10) UNSIGNED,
  `StartDateTime` datetime default NULL,
  `EndDateTime` datetime default NULL,
  `Interval`  INT(10) UNSIGNED,
  PRIMARY KEY(Id)
) ENGINE=InnoDB;"
  ));

PREPARE stmt FROM @s;
EXECUTE stmt;
