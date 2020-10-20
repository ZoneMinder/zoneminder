--
-- This adds Sessions Table
--

SET @s = (SELECT IF(
    (SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.TABLES
      WHERE table_name = 'Sessions'
      AND table_schema = DATABASE()
    ) > 0,
    "SELECT 'Sessions table exists'",
    "CREATE TABLE Sessions (
  id char(32) not null,
  access INT(10) UNSIGNED DEFAULT NULL,
  data text,
  PRIMARY KEY(id)
) ENGINE=InnoDB;"
  ));

PREPARE stmt FROM @s;
EXECUTE stmt;
