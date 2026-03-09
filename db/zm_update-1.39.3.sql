--
-- Add Menu_Items table for customizable navbar/sidebar menu
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = DATABASE()
     AND table_name = 'Menu_Items'
    ) > 0,
"SELECT 'Table Menu_Items already exists'",
"CREATE TABLE `Menu_Items` (
  `Id`        int(10) unsigned NOT NULL AUTO_INCREMENT,
  `MenuKey`   varchar(32) NOT NULL,
  `Enabled`   tinyint(1) NOT NULL DEFAULT 1,
  `Label`     varchar(64) DEFAULT NULL,
  `SortOrder` smallint NOT NULL DEFAULT 0,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Menu_Items_MenuKey_idx` (`MenuKey`)
) ENGINE=InnoDB"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Seed default menu items if table is empty
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM `Menu_Items`) > 0,
"SELECT 'Menu_Items already has data'",
"INSERT INTO `Menu_Items` (`MenuKey`, `Enabled`, `SortOrder`) VALUES
  ('Console', 1, 10),
  ('Montage', 1, 20),
  ('MontageReview', 1, 30),
  ('Events', 1, 40),
  ('Options', 1, 50),
  ('Log', 1, 60),
  ('Devices', 1, 70),
  ('IntelGpu', 1, 80),
  ('Groups', 1, 90),
  ('Filters', 1, 100),
  ('Snapshots', 1, 110),
  ('Reports', 1, 120),
  ('ReportEventAudit', 1, 130),
  ('Map', 1, 140)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
