--
-- Add CanIndicatorLight capability column to Controls (camera indicator LED on/off).
--
SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE table_schema = DATABASE()
       AND table_name = 'Controls'
       AND column_name = 'CanIndicatorLight') > 0,
  'SELECT ''Column CanIndicatorLight already exists''',
  'ALTER TABLE `Controls` ADD COLUMN `CanIndicatorLight` tinyint(3) unsigned NOT NULL default ''0'' AFTER `CanLight`'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Add model-specific Controls entry for the Amcrest ASH42-B.
-- This model has no PTZ; its indicator LED is controlled via LightGlobal config
-- (configManager.setConfig/getConfig, verified live). Reboot via magicBox.reboot.
--
INSERT INTO `Controls`
  (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,`CanIndicatorLight`)
SELECT 'Amcrest ASH42-B RPC','Ffmpeg','Dahua_RPC',1,1,1
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `Controls` WHERE `Name`='Amcrest ASH42-B RPC');

--
-- The ASH21-B and ADC2W also control their indicator LED via LightGlobal
-- (verified live on both models), so enable the capability on their entries.
--
UPDATE `Controls` SET `CanIndicatorLight`=1
 WHERE `Name` IN ('Amcrest ASH21-B RPC','Amcrest ADC2W RPC');

--
-- The generic entry keeps every capability enabled for testing new cameras.
-- (CanLight column was added by 1.39.12, CanIndicatorLight above.)
--
UPDATE `Controls` SET `CanLight`=1, `CanIndicatorLight`=1
 WHERE `Name`='Dahua/Amcrest RPC';
