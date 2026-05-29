--
-- Widen DefaultScale to preserve fit_to_width and repair previously truncated values.
--

ALTER TABLE Monitors MODIFY DefaultScale VARCHAR(16) NOT NULL default '0';
UPDATE Monitors SET DefaultScale = 'fit_to_width' WHERE DefaultScale = 'fit_to';

ALTER TABLE MonitorPresets MODIFY DefaultScale VARCHAR(16) NOT NULL default '0';
UPDATE MonitorPresets SET DefaultScale = 'fit_to_width' WHERE DefaultScale = 'fit_to';
