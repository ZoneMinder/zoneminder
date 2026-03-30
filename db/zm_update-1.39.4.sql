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
