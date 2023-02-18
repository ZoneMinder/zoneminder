--
-- This adds StorageAreas
--

SELECT 'Checking For Server_Stats Table';
SET @s = (SELECT IF(
    (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE table_name = 'Server_Stats'
    AND table_schema = DATABASE()
    ) > 0,
"SELECT 'Server_Stats table exists'",
"CREATE TABLE `Server_Stats` (
  `Id` int(10) unsigned NOT NULL auto_increment,
  `ServerId`  int(10) unsigned,
  `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `CpuLoad` DECIMAL(5,1) default NULL,
  `TotalMem` bigint unsigned default null,
  `FreeMem` bigint unsigned default null,
  `TotalSwap` bigint  unsigned default null,
  `FreeSwap` bigint unsigned default null,
  PRIMARY KEY (Id)
)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SELECT 'Create Index For TimeStamp on Server_Stats';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE table_name = 'Server_Stats'
  AND table_schema = DATABASE()
  AND index_name = 'Server_Stats_TimeStamp_idx'
  ) > 0,
"SELECT 'TimeStamp Index already exists on Server_Stats table'",
"CREATE INDEX `Server_Stats_TimeStamp_idx` ON `Server_Stats` (`TimeStamp`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
