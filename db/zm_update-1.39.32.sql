--
-- Add a Controls entry for the AMLINK AL5M white light.
--
-- These cameras carry a white light that is reachable only over a vendor
-- JSON-RPC tunnel at /Onvif/device_service -- note the capital O, which is a
-- different endpoint from the ONVIF SOAP service at /onvif/device_service.
-- ONVIF itself exposes no light on this hardware: imaging offers only
-- Brightness/ColorSaturation/Contrast/Sharpness, RelayOutputs is 0, and there
-- is no PTZ service and so no auxiliary commands.
--
-- The camera speaks Dahua's RPC vocabulary, so ZoneMinder::Control::AMLink
-- subclasses Dahua_RPC and replaces only the transport.  CanReboot comes along
-- with that inheritance.  Nothing else is set: there is no PTZ, and the ONVIF
-- imaging controls stay with the ONVIF protocol module.
--

SELECT 'Checking for the AMLINK AL5M Controls entry';
SET @s = (SELECT IF(
  (SELECT COUNT(*) FROM `Controls` WHERE `Protocol` = 'AMLink') > 0,
"SELECT 'AMLINK AL5M control already exists'",
"INSERT INTO `Controls` (`Name`,`Type`,`Protocol`,`CanReboot`,`CanLight`) VALUES ('AMLINK AL5M (light)','Ffmpeg','AMLink',1,1)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
