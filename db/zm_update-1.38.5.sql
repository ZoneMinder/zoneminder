--
-- Running this file also makes zmupdate.pl load the new ZM_AUTH_TRUSTED_PROXIES
-- option into the Config table on upgrade to 1.38.5; the daemons refuse to start
-- while the Config table is missing options.
--

--
-- Add an index on Sessions.access to support session garbage collection.
--

SET @s = (SELECT IF(
  (SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE table_name = 'Sessions'
    AND table_schema = DATABASE()
    AND index_name = 'Sessions_access_idx'
  ) > 0,
  "SELECT 'access Index already exists on Sessions table'",
  "CREATE INDEX Sessions_access_idx ON Sessions (`access`)"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

--
-- ZoneMinder::Control::onvif has been removed. It collided with
-- ZoneMinder::Control::ONVIF on case insensitive filesystems, where only one of
-- the two files can exist. Fresh installs have seeded Protocol='ONVIF' since the
-- unified module landed, but nothing ever moved the rows on upgraded installs,
-- so any install created before then still points at the deleted module.
--

UPDATE `Controls` SET `Protocol`='ONVIF' WHERE `Protocol`='onvif';
