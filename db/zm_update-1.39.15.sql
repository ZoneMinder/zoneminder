--
-- This updates a 1.39.14 database to 1.39.15
--
-- Add a control entry for the HiSilicon Hi3510 CGI PTZ protocol used by
-- many inexpensive IP cameras (e.g. Tenvis TH661).
--

INSERT INTO `Controls`
  (`Name`, `Type`, `Protocol`, `CanReset`, `HasPresets`, `NumPresets`, `CanSetPresets`,
   `CanMove`, `CanMoveDiag`, `CanMoveCon`, `CanPan`, `CanTilt`)
SELECT 'HiSilicon Hi3510 CGI', 'Ffmpeg', 'HiSilicon_Hi3510_CGI', 1, 1, 10, 1, 1, 1, 1, 1, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `Controls` WHERE `Protocol` = 'HiSilicon_Hi3510_CGI');
