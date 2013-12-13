--
-- This updates a 1.26.4 database to 1.26.5
--

--
-- Add AlarmRefBlendPerc field for controlling the reference image blend percent during alarm (see pull request #241)
--
ALTER TABLE `Monitors` ADD `AlarmRefBlendPerc` TINYINT(3) UNSIGNED NOT NULL DEFAULT '3' AFTER `RefBlendPerc`;

