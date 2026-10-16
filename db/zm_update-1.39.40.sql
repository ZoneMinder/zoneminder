--
-- This updates a 1.39.39 database to 1.39.40
--

--
-- Put the presentation settings on the Display tab.
--
-- The skin and css, the language and locale, the date, time and datetime
-- formats, the default bandwidth and the snapshots feature switch all decide
-- how the interface looks or what it offers, which is what that tab is for,
-- but they sat under System among the shutdown command, the shm key and the
-- audit intervals. System drops from 42 entries to 33.
--
-- The snapshots switch is read only by the skin: it gates a button in montage
-- and montage review, a menu item, and the snapshot views and their
-- permission checkboxes. Nothing in the daemons or the perl scripts looks at
-- it.
--
-- The category key is still 'web' -- that is the tab now labelled Display.
-- The tab whose key is 'display' holds the per-browser cookie settings and
-- stores no configuration at all, so a stored default does not belong there.
--
-- ZM_TIMEZONE stays under System. It reads like a presentation setting, but
-- Filter.pm uses it for date arithmetic when evaluating filters, so it
-- changes behaviour and not just what is displayed.
--
-- A migration is needed as well as the ConfigData change because a version
-- upgrade does not rewrite Config: zmupdate only reloads it from ConfigData
-- under --freshen, which is mutually exclusive with an upgrade run. A fresh
-- install gets the new category from the generated zm_create.sql.
--
UPDATE `Config` SET `Category` = 'web'
 WHERE `Name` IN (
   'ZM_SKIN_DEFAULT',
   'ZM_CSS_DEFAULT',
   'ZM_BANDWIDTH_DEFAULT',
   'ZM_LANG_DEFAULT',
   'ZM_LOCALE_DEFAULT',
   'ZM_DATE_FORMAT_PATTERN',
   'ZM_TIME_FORMAT_PATTERN',
   'ZM_DATETIME_FORMAT_PATTERN',
   'ZM_FEATURES_SNAPSHOTS'
 );
