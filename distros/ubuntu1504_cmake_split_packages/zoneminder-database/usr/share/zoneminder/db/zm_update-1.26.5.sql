--
-- This updates a 1.26.4 database to 1.26.5
--

--
-- Add AlarmRefBlendPerc field for controlling the reference image blend percent during alarm (see pull request #241)
--

SET @s = (SELECT IF(
	(SELECT COUNT(*)
	FROM INFORMATION_SCHEMA.COLUMNS
	WHERE table_name = 'Monitors'
	AND table_schema = DATABASE()
	AND column_name = 'AlarmRefBlendPerc'
	) > 0,
"SELECT 1",
"ALTER TABLE `Monitors` ADD `AlarmRefBlendPerc` TINYINT(3) UNSIGNED NOT NULL DEFAULT '6' AFTER `RefBlendPerc`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

UPDATE `Monitors` SET `AlarmRefBlendPerc` = `RefBlendPerc`;


