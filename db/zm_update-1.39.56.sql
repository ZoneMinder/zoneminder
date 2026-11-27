--
-- This updates a 1.39.55 database to 1.39.56
--

--
-- Widen Users.Language so every language in web/lang fits (big5_big5 is 9 characters).
--

ALTER TABLE `Users` MODIFY `Language` varchar(16);
