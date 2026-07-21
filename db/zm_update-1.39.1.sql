--
-- Add AUDIT logging level between PANIC (-4) and NOLOG.
-- AUDIT is now -5; NOLOG shifts from -5 to -6.
-- Migrate any stored NOLOG config values from -5 to -6.
--

UPDATE Config SET Value = '-6'
  WHERE Name IN ('ZM_LOG_LEVEL_SYSLOG','ZM_LOG_LEVEL_TERM',
    'ZM_LOG_LEVEL_FILE','ZM_LOG_LEVEL_WEBLOG','ZM_LOG_LEVEL_DATABASE')
  AND Value = '-5';

UPDATE Config SET DefaultValue = '-6'
  WHERE Name IN ('ZM_LOG_LEVEL_SYSLOG','ZM_LOG_LEVEL_TERM',
    'ZM_LOG_LEVEL_FILE','ZM_LOG_LEVEL_WEBLOG','ZM_LOG_LEVEL_DATABASE')
  AND DefaultValue = '-5';
