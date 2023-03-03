DROP TABLE IF EXISTS `Magic_Links`;

CREATE TABLE `Magic_Links` (
  `Id` int NOT NULL AUTO_INCREMENT,
  `UserId` varchar(99) COLLATE utf8mb4_general_ci NOT NULL,
  `Token` varchar(99) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CreatedOn` TimeStamp NOT NULL default NOW(),
  PRIMARY KEY(Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
