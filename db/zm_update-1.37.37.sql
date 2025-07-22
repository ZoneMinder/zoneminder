SELECT 'Checking for CpuUserPercent in ServerStats';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Server_Stats'
  AND table_schema = DATABASE()
  AND column_name = 'CpuUserPercent'
  ) > 0,
"SELECT 'Column CpuUserPercent already exists in Server_Stats'",
"ALTER TABLE Server_Stats ADD `CpuUserPercent` DECIMAL(5,1) default NULL AFTER `CpuLoad`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SELECT 'Checking for CpuSystemPercent in ServerStats';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Server_Stats'
  AND table_schema = DATABASE()
  AND column_name = 'CpuSystemPercent'
  ) > 0,
"SELECT 'Column CpuSystemPercent already exists in Server_Stats'",
"ALTER TABLE Server_Stats ADD `CpuSystemPercent` DECIMAL(5,1) default NULL AFTER `CpuUserPercent`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
