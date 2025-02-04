--
-- Table structure for table `Users_Preferences`
--

DROP TABLE IF EXISTS `User_Preferences`;
CREATE TABLE `User_Preferences` (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `UserId` int(10) unsigned NOT NULL,
  FOREIGN KEY (UserId) REFERENCES Users(Id),
  `Name`  varchar(64),
  `Value` TEXT,
  PRIMARY KEY (Id)
);

CREATE INDEX User_Preferences_UserID_idx on User_Preferences (`UserId`);
