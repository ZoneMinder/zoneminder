--
-- Add a model-specific Controls entry for the LTS CMIP1342WE-28MDA.
--
-- This is a fixed ColorVu camera (no PTZ, no motorised focus/iris). Its white
-- light is driven through the HikVision/LTS ISAPI supplement-light interface
-- (ISAPI/Image/channels/1/supplementLight: colorVuWhiteLight/eventIntelligence/
-- irLight/close), so only CanLight and CanReboot apply. CanReset stays 0 because
-- the HikVision module implements reboot but no reset.
--
INSERT INTO `Controls`
  (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,`CanLight`)
SELECT 'LTS CMIP1342WE-28MDA','Ffmpeg','HikVision',0,1,1
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `Controls` WHERE `Name`='LTS CMIP1342WE-28MDA');

--
-- Same for the LTS CMIP3CD42WI-28AISP: another fixed ColorVu camera with the
-- same white-light interface and no PTZ/focus/iris.
--
INSERT INTO `Controls`
  (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,`CanLight`)
SELECT 'LTS CMIP3CD42WI-28AISP','Ffmpeg','HikVision',0,1,1
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `Controls` WHERE `Name`='LTS CMIP3CD42WI-28AISP');

--
-- Add Link column to Menu_Items so custom menu entries can point at a URL
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Menu_Items' AND column_name = 'Link'
    ) > 0,
"SELECT 'Column Link already exists'",
"ALTER TABLE `Menu_Items` ADD `Link` varchar(255) DEFAULT NULL AFTER `IconType`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
