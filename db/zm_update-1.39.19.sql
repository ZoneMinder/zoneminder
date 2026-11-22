--
-- Make User_Preferences unique per (UserId, Name).
--
-- Nothing enforced this, but every reader already assumes one row per name:
-- User::Preferences() hashes the rows by Name, montagereview.php and
-- User_Preference::find_one() take the first match, and TagOrder::load()
-- reads a single Value. With duplicates present those pick an arbitrary row,
-- so a write could land on one row while reads returned another.
--
-- Make Name NOT NULL, collapse any existing duplicates keeping the highest Id
-- (the most recently inserted), then replace the UserId-only index with a
-- UNIQUE (UserId, Name) index. The unique index still serves UserId-only
-- lookups and the UserId foreign key as a leftmost prefix, so the old one is
-- redundant.
--

-- A preference is only ever reached by name, so a NULL-named row is
-- unreachable: User::Preferences() can't hash it and find_one(['Name'=>...])
-- can't match it. Discard them rather than folding them into '', which would
-- just manufacture duplicates. NULLs must go before the unique index is added
-- anyway -- a unique index permits any number of NULLs, so it would not
-- actually constrain them.
DELETE FROM User_Preferences WHERE Name IS NULL;

SET @s = (SELECT IF(
  (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE table_name = 'User_Preferences'
    AND table_schema = DATABASE()
    AND column_name = 'Name'
    AND is_nullable = 'YES'
  ) > 0,
  "ALTER TABLE `User_Preferences` MODIFY `Name` varchar(64) NOT NULL",
  "SELECT 'User_Preferences.Name is already NOT NULL'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

DELETE up FROM User_Preferences AS up
  INNER JOIN (
    SELECT UserId, Name, MAX(Id) AS KeepId
      FROM User_Preferences
     GROUP BY UserId, Name
  ) AS keep
    ON up.UserId = keep.UserId AND up.Name = keep.Name
 WHERE up.Id <> keep.KeepId;

-- Add the unique index before dropping the old one, so the UserId foreign key
-- always has an index it can use.
SET @s = (SELECT IF(
  (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_name = 'User_Preferences'
    AND table_schema = DATABASE()
    AND index_name = 'User_Preferences_UserId_Name_idx'
  ) > 0,
  "SELECT 'User_Preferences_UserId_Name_idx already exists on User_Preferences table'",
  "ALTER TABLE `User_Preferences` ADD UNIQUE INDEX `User_Preferences_UserId_Name_idx` (`UserId`, `Name`)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
  (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_name = 'User_Preferences'
    AND table_schema = DATABASE()
    AND index_name = 'User_Preferences_UserID_idx'
  ) > 0,
  "ALTER TABLE `User_Preferences` DROP INDEX `User_Preferences_UserID_idx`",
  "SELECT 'User_Preferences_UserID_idx already removed from User_Preferences table'"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
