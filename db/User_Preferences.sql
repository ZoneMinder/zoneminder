--
-- Table structure for table `Users_Preferences`
--

DROP TABLE IF EXISTS `User_Preferences`;
CREATE TABLE `User_Preferences` (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `UserId` int(10) unsigned NOT NULL,
  FOREIGN KEY (UserId) REFERENCES Users(Id),
  `Name`  varchar(64) NOT NULL,
  `Value` TEXT,
  PRIMARY KEY (Id)
);

-- A user has at most one value per preference name. This also serves
-- UserId-only lookups (and the UserId foreign key) as a leftmost prefix, so
-- no separate UserId index is needed.
CREATE UNIQUE INDEX User_Preferences_UserId_Name_idx on User_Preferences (`UserId`, `Name`);
