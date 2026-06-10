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
