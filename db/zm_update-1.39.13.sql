--
-- Add CanLight capability column to Controls (light on/off control).
--
SELECT 'Adding CanLight to Controls' AS '';
SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE table_schema = DATABASE()
       AND table_name = 'Controls'
       AND column_name = 'CanLight') > 0,
  'SELECT ''Column CanLight already exists''',
  'ALTER TABLE `Controls` ADD COLUMN `CanLight` tinyint(3) unsigned NOT NULL default ''0'' AFTER `MaxWhiteSpeed`'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Enable the light control on the Amcrest ADC2W row.
--
UPDATE `Controls` SET `CanLight`=1 WHERE `Name`='Amcrest ADC2W RPC';
