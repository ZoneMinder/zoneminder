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

--
-- Add ZM_WEB_BUTTON_STYLE config option
--

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM Config WHERE Name = 'ZM_WEB_BUTTON_STYLE') > 0,
"SELECT 'ZM_WEB_BUTTON_STYLE already exists'",
"INSERT INTO Config SET Id = 251, Name = 'ZM_WEB_BUTTON_STYLE', Value = 'icons+text', Type = 'string', DefaultValue = 'icons+text', Hint = 'icons+text|icons|text', Pattern = '(?^i:^([it]))', Format = ' $1 ', Prompt = 'How to display toolbar buttons throughout the interface', Help = 'Controls the display of toolbar buttons across the web interface. Icons + Text: Show both icon and label (default). Icons Only: Show only the icon; labels are hidden. Text Only: Show only the label; icons are hidden on buttons that have labels.', Category = 'web', Readonly = '0', Requires = ''"
));

PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
