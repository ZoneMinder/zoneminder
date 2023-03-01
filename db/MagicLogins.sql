CREATE TABLE `MagicLogins` (
  `Id` int NOT NULL,
  `UserId` varchar(99) COLLATE utf8mb4_general_ci NOT NULL,
  `Token` varchar(99) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CreatedOn` TimeStamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
