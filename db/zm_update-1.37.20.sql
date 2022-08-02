
INSERT INTO zm.Config
(Id, Name, Value, `Type`, DefaultValue, Hint, Pattern, Format, Prompt, Help, Category, Readonly, Private, `System`, Requires)
VALUES(224, 'ZM_OPT_USE_ALARMSERVER', '0', 'boolean', 'no', 'yes|no', '(?^i:^([yn]))', ' ($1 =~ /^y/) ? ''yes'' : ''no'' ', 'Enable NETSurveillance WEB Camera ALARM SERVER', '
      Alarm Server that works with cameras that use Netsurveillance Web Server, 
      and has the Alarm Server option it receives alarms sent by this cameras 
      (once enabled), and pass to Zoneminder the events. 
      It requires pyzm installed, visit https://pyzm.readthedocs.io/en/latest/
      for installation instructions.
      ', 'system', 0, 0, 0, NULL);

INSERT INTO zm.Config
(Id, Name, Value, `Type`, DefaultValue, Hint, Pattern, Format, Prompt, Help, Category, Readonly, Private, `System`, Requires)
VALUES(225, 'ZM_OPT_ALS_LOGENTRY', '0', 'boolean', 'no', 'yes|no', '(?^i:^([yn]))', ' ($1 =~ /^y/) ? ''yes'' : ''no'' ', '
      Makes ALARM SERVER create a log entry in ZoneMinder', '',
      'system', 0, 0, 0, NULL);

INSERT INTO zm.Config
(Id, Name, Value, `Type`, DefaultValue, Hint, Pattern, Format, Prompt, Help, Category, Readonly, Private, `System`, Requires)
VALUES(226, 'ZM_OPT_ALS_ALARM', '0', 'boolean', 'no', 'yes|no', '(?^i:^([yn]))', ' ($1 =~ /^y/) ? ''yes'' : ''no'' ', '
      Send the Human Detected event on ALARM SERVER to ZoneMinder, It does not work along with OPT_ALS_TRIGGEREVENT...',
      '', 'system', 0, 0, 0, NULL);

INSERT INTO zm.Config
(Id, Name, Value, `Type`, DefaultValue, Hint, Pattern, Format, Prompt, Help, Category, Readonly, Private, `System`, Requires)
VALUES(227, 'ZM_OPT_ALS_TRIGGEREVENT', '0', 'boolean', 'no', 'yes|no', '(?^i:^([yn]))', ' ($1 =~ /^y/) ? ''yes'' : ''no'' ', '
      Trigger and event on Human Detected event on to Netsurveillance ALARM SERVER to ZoneMinder. Requires the zmTrigger option Enabled', 
      ' ', 'system', 0, 0, 0, NULL);


INSERT INTO zm.Config
(Id, Name, Value, `Type`, DefaultValue, Hint, Pattern, Format, Prompt, Help, Category, Readonly, Private, `System`, Requires)
VALUES(228, 'ZM_OPT_ALS_PORT', '15002', 'integer', '15002', 'XXXXX', '(?^i:^([yn]))', ' ($1 =~ /^y/) ? ''yes'' : ''no'' ', 'Port Number to receive events from Alarm Server', 
     '','system', 0, 0, 0, NULL);
