--
-- This updates a 1.39.10 database to 1.39.11
--
-- Added the ability to disable ZMS
--
ALTER TABLE `Monitors` ADD COLUMN `ZMSEnabled` BOOLEAN NOT NULL default true AFTER `WhatDisplay`
