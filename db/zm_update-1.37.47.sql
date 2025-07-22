--
-- This adds Manufacturers and Models
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'User_Preferences'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'User_Preferences table exists'",
    "
CREATE TABLE `User_Preferences` (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `UserId` int(10) unsigned NOT NULL,
  FOREIGN KEY (UserId) REFERENCES Users(Id),
  `Name`  varchar(64),
  `Value` TEXT,
  PRIMARY KEY (Id)
)
    "
  ));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE table_name = 'User_Preferences'
  AND table_schema = DATABASE()
  AND index_name = 'User_Preferences_UserID_idx'
  ) > 0,
"SELECT 'UserId Index already exists on User_Preferences table'",
"CREATE INDEX User_Preferences_UserID_idx on User_Preferences (`UserId`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
