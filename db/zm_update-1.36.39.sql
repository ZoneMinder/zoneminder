--
-- ZM_UPLOAD_PASS holds the password for the remote upload account but shipped
-- with Private = 0, so the Config API returned it to System=View accounts and
-- the web UI wrote it into every page as a JavaScript constant for any logged-in
-- user. Mark it Private so it is withheld like the other secrets.
-- refs GHSA-8g63-hqg8-82cv
--
UPDATE `Config` SET `Private` = 1 WHERE `Name` = 'ZM_UPLOAD_PASS';
