--
-- Increase ONVIF_Options column width from 64 to 255 characters
--

ALTER TABLE `Monitors` MODIFY `ONVIF_Options` VARCHAR(255) NOT NULL DEFAULT '';
