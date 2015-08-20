--
-- This updates a 1.28.104 database to 1.28.105
--

--
-- Add Blacken type to Zone Types
--

alter table Zones       modify Type enum('Active','Inclusive','Exclusive','Preclusive','Inactive','Blacken') NOT NULL DEFAULT 'Active';
alter table ZonePresets modify Type enum('Active','Inclusive','Exclusive','Preclusive','Inactive','Blacken') NOT NULL DEFAULT 'Active';
