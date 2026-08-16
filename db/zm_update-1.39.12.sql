--
-- Add Controls definition for the Dahua/Amcrest JSON-RPC PTZ protocol.
--
INSERT INTO `Controls`
  (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,`CanZoom`,`CanZoomCon`,`HasPresets`,`NumPresets`,`HasHomePreset`,`CanSetPresets`,`CanMove`,`CanMoveDiag`,`CanMoveCon`,`CanPan`,`CanTilt`)
SELECT 'Dahua/Amcrest RPC','Ffmpeg','Dahua_RPC',1,1,1,1,1,25,1,1,1,1,1,1,1
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `Controls` WHERE `Name`='Dahua/Amcrest RPC');

--
-- Add a model-specific Controls entry for the Amcrest ASH21-B. This model
-- exposes only pan/tilt over the Dahua RPC interface: SetPreset is a no-op,
-- zoom does nothing, and the firmware reports all four diagonals as
-- unsupported (verified live against the camera).
--
INSERT INTO `Controls`
  (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,`CanZoom`,`CanZoomCon`,`HasPresets`,`NumPresets`,`HasHomePreset`,`CanSetPresets`,`CanMove`,`CanMoveDiag`,`CanMoveCon`,`CanPan`,`CanTilt`)
SELECT 'Amcrest ASH21-B RPC','Ffmpeg','Dahua_RPC',1,1,0,0,0,0,0,0,1,0,1,1,1
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `Controls` WHERE `Name`='Amcrest ASH21-B RPC');

--
-- Add a model-specific Controls entry for the Amcrest ADC2W. This model has
-- no PTZ and its white light is cloud-only (not exposed over local RPC), so
-- the only locally controllable action is reboot via magicBox.reboot
-- (verified live against the camera).
--
INSERT INTO `Controls`
  (`Name`,`Type`,`Protocol`,`CanReset`,`CanReboot`,`CanZoom`,`CanZoomCon`,`HasPresets`,`NumPresets`,`HasHomePreset`,`CanSetPresets`,`CanMove`,`CanMoveDiag`,`CanMoveCon`,`CanPan`,`CanTilt`)
SELECT 'Amcrest ADC2W RPC','Ffmpeg','Dahua_RPC',1,1,0,0,0,0,0,0,0,0,0,0,0
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `Controls` WHERE `Name`='Amcrest ADC2W RPC');

--
-- Add the ASH21-B and ADC2W models under the Amcrest manufacturer (id 2).
--
INSERT INTO `Models` (`Name`,`ManufacturerId`)
SELECT 'ASH21-B', 2
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `Models` WHERE `Name`='ASH21-B');

INSERT INTO `Models` (`Name`,`ManufacturerId`)
SELECT 'ADC2W', 2
  FROM DUAL
 WHERE NOT EXISTS (SELECT 1 FROM `Models` WHERE `Name`='ADC2W');
