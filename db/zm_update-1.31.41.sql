
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Storage'
     AND column_name = 'DoDelete'
    ) > 0,
"SELECT 'Column DoDelete already exists in Storage'",
"ALTER TABLE `Storage` ADD `DoDelete` BOOLEAN NOT NULL default true AFTER `ServerId`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE()
     AND table_name = 'Events'
     AND column_name = 'Locked'
    ) > 0,
"SELECT 'Column Locked already exists in Events'",
"ALTER TABLE `Events` ADD `Locked` BOOLEAN NOT NULL default false AFTER `Scheme`"
));

PREPARE stmt FROM @s;
EXECUTE stmt;


ALTER TABLE `Frames` MODIFY `Id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `Events` MODIFY `Id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `Frames` MODIFY `EventId` BIGINT UNSIGNED NOT NULL;
ALTER TABLE `Stats` MODIFY `EventId` BIGINT UNSIGNED NOT NULL;

ALTER TABLE `Events_Hour` MODIFY `EventId` BIGINT UNSIGNED NOT NULL;
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_TYPE='FOREIGN KEY'
     AND table_name = 'Events'
     AND column_name = 'Locked'
    ) > 0,
"SELECT 'Column Locked already exists in Events'",
"ALTER TABLE `Events` ADD `Locked` BOOLEAN NOT NULL default false AFTER `Scheme`"
));
ALTER TABLE `Events_Hour` ADD CONSTRAINT Events_Hour_EventId_fk FOREIGN KEY (`EventId`) REFERENCES `Events`(`Id`) ON DELETE CASCADE;
ALTER TABLE `Events_Day` MODIFY `EventId` BIGINT UNSIGNED NOT NULL;
ALTER TABLE `Events_Day` ADD CONSTRAINT Events_Day_EventId_fk FOREIGN KEY (`EventId`) REFERENCES `Events`(`Id`) ON DELETE CASCADE;
ALTER TABLE `Events_Week` MODIFY `EventId` BIGINT UNSIGNED NOT NULL;
ALTER TABLE `Events_Week` ADD CONSTRAINT Events_Week_EventId_fk FOREIGN KEY (`EventId`) REFERENCES `Events`(`Id`) ON DELETE CASCADE;
ALTER TABLE `Events_Month` MODIFY `EventId` BIGINT UNSIGNED NOT NULL;
ALTER TABLE `Events_Month` ADD CONSTRAINT Events_Month_EventId_fk FOREIGN KEY (`EventId`) REFERENCES `Events`(`Id`) ON DELETE CASCADE;
ALTER TABLE `Events_Archived` MODIFY `EventId` BIGINT UNSIGNED NOT NULL;
ALTER TABLE `Events_Archived` ADD CONSTRAINT Events_Archived_EventId_fk FOREIGN KEY (`EventId`) REFERENCES `Events`(`Id`) ON DELETE CASCADE;

ALTER TABLE `Stats` ADD CONSTRAINT Stats_MonitorId_fk FOREIGN KEY (`MonitorId`) REFERENCES `Monitors`(`Id`) ON DELETE CASCADE;
ALTER TABLE `Stats` ADD CONSTRAINT Stats_EventId_fk FOREIGN KEY (`EventId`) REFERENCES `Events`(`Id`) ON DELETE CASCADE;
ALTER TABLE `Stats` ADD CONSTRAINT Stats_ZoneId_fk FOREIGN KEY (`ZoneId`) REFERENCES `Zones`(`Id`) ON DELETE CASCADE;

ALTER TABLE `Frames` ADD CONSTRAINT Frames_EventId_fk FOREIGN KEY (`EventId`) REFERENCES `Events`(`Id`) ON DELETE CASCADE;
ALTER TABLE `Frames` DROP INDEX `Type`;

