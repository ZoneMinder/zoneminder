--
-- Add Quadra menu item for existing installs
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM `Menu_Items` WHERE `MenuKey` = 'Quadra') > 0,
"SELECT 'Quadra menu item already exists'",
"INSERT INTO `Menu_Items` (`MenuKey`, `Enabled`, `SortOrder`) VALUES ('Quadra', 1, 85)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Convert ZM_OPT_USE_REMEMBER_ME from boolean to tri-state (None/Yes/No)
--

UPDATE Config SET
  Value = CASE WHEN Value = '1' THEN 'No' ELSE 'None' END,
  Type = 'string',
  DefaultValue = 'None',
  Hint = 'None|Yes|No',
  Pattern = '(?^i:^([YyNn]))',
  Format = ' $1 ',
  Prompt = 'Show a "Remember Me" option on the login page',
  Help = '
      Controls whether a "Remember Me" checkbox appears on the login page.
      None: No checkbox is shown. Sessions always persist for ZM_COOKIE_LIFETIME.
      Yes: Checkbox is shown and checked by default. Users may uncheck it for a session-only cookie.
      No: Checkbox is shown and unchecked by default. Users may check it to persist the session.
      '
WHERE Name = 'ZM_OPT_USE_REMEMBER_ME';
