--
-- Update Config table to have System BOOLEAN field
--

SELECT 'Checking for System in Config';
SET @s = (SELECT IF(
  (SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE table_name = 'Config'
  AND table_schema = DATABASE()
  AND column_name = 'System'
  ) > 0,
"SELECT 'Column System already exists in Config'",
"ALTER TABLE `Config` ADD COLUMN `System` BOOLEAN NOT NULL DEFAULT FALSE AFTER `Readonly`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;


UPDATE MontageLayouts SET `Positions`='{ "default":{"float":"left", "width":"50%","left":"0px","right":"0px","top":"0px","bottom":"0px"} }' WHERE Name='2 Wide';
UPDATE MontageLayouts SET `Positions`='{ "default":{"float":"left", "width":"33.3%","left":"0px","right":"0px","top":"0px","bottom":"0px"} }' WHERE Name='3 Wide';
UPDATE MontageLayouts SET `Positions`='{ "default":{"float":"left", "width":"25%","left":"0px","right":"0px","top":"0px","bottom":"0px"} }' WHERE Name='4 Wide';
