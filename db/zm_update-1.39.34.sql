--
-- This updates a 1.39.33 database to 1.39.34
--
-- ZoneMinder::Control::onvif has been removed. It collided with
-- ZoneMinder::Control::ONVIF on case insensitive filesystems, where only one of
-- the two files can exist. Fresh installs have seeded Protocol='ONVIF' since the
-- unified module landed, but nothing ever moved the rows on upgraded installs,
-- so any install created before then still points at the deleted module.
--
-- zm_update-1.35.23.sql sent Protocol='onvif' to FoscamCGI, which was correct at
-- the time: onvif.pm then held an implementation of the Foscam CGI protocol and
-- was copied to FoscamCGI.pm. It was afterwards replaced with a real ONVIF
-- implementation and the seed row was restored, so rows written since that point
-- belong on ONVIF, not FoscamCGI.
--

UPDATE `Controls` SET `Protocol`='ONVIF' WHERE `Protocol`='onvif';
