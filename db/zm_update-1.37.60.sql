-- zm.floorplan definition

CREATE TABLE `floorplan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mid` varchar(10) NOT NULL,
  `ismon` tinyint(1) DEFAULT NULL,
  `json` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='keeps shapes and coordinates of floorplan';
