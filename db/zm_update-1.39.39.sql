--
-- This updates a 1.39.38 database to 1.39.39
--

--
-- Drop Controls.Type. 1.39.38 narrowed its enum alongside Monitors and
-- MonitorPresets; this removes the column itself.
--
-- It looks like the monitor Type the control applies to, and has been kept in
-- step with the Monitors enum since 1.27, but nothing has ever read it. The
-- control dropdown on the monitor page lists every row by Name, no query
-- filters on it, and no code compares it to the monitor's own Type. It is a
-- field the control edit form asks for and the control list displays back.
--
-- The real version of this idea is MonitorPresets.Type, which does decide
-- what a preset creates. Controls appears to have grown the column by
-- analogy with that, which is why the two were always altered together.
--
ALTER TABLE `Controls` DROP COLUMN `Type`;
