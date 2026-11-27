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
-- Guarded so the file stays re-runnable: DROP COLUMN is the one statement
-- here that fails rather than no-ops the second time. Scoped to DATABASE()
-- so a same-named column in another schema cannot satisfy the check.
set @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE table_schema = DATABASE()
                  AND table_name = 'Controls' AND column_name = 'Type');
set @sqlstmt := if(@exist > 0,
  'ALTER TABLE `Controls` DROP COLUMN `Type`',
  "SELECT 'Controls.Type has already been dropped'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Dropping the column leaves the two shipped Pelco controls duplicated.
--
-- Each existed twice, once typed Local and once Ffmpeg, identical in every
-- other column: someone with an IP camera and a serial ptz head reasonably
-- assumed the Local row would not apply to an Ffmpeg monitor and added a
-- copy, though both always worked because nothing read the type. With the
-- column gone the pair is indistinguishable, so the later row is removed.
--
-- Pelco-D and Pelco-P are NOT merged with each other. Their capability flags
-- match, but Protocol differs and selects the control module, PelcoD.pm or
-- PelcoP.pm; they are different wire protocols that happen to have the same
-- feature set.
--
-- Monitors pointing at the row being removed are moved to the survivor
-- first, so no monitor loses its ptz. Scoped to these two names rather than
-- deduplicating the table generally: a user's own same-named controls may
-- differ in ways this cannot see, and silently merging them would discard
-- their edits.
--
UPDATE `Monitors` `m`
  JOIN `Controls` `dup` ON `m`.`ControlId` = `dup`.`Id`
  JOIN (SELECT `Name`, `Protocol`, MIN(`Id`) AS `keep_id`
          FROM `Controls`
         WHERE `Name` IN ('Pelco-D', 'Pelco-P')
         GROUP BY `Name`, `Protocol`
        HAVING COUNT(*) > 1) `k`
    ON `k`.`Name` = `dup`.`Name` AND `k`.`Protocol` <=> `dup`.`Protocol`
   SET `m`.`ControlId` = `k`.`keep_id`
 WHERE `dup`.`Id` <> `k`.`keep_id`;

DELETE `dup` FROM `Controls` `dup`
  JOIN (SELECT `Name`, `Protocol`, MIN(`Id`) AS `keep_id`
          FROM `Controls`
         WHERE `Name` IN ('Pelco-D', 'Pelco-P')
         GROUP BY `Name`, `Protocol`
        HAVING COUNT(*) > 1) `k`
    ON `k`.`Name` = `dup`.`Name` AND `k`.`Protocol` <=> `dup`.`Protocol`
 WHERE `dup`.`Id` <> `k`.`keep_id`;
