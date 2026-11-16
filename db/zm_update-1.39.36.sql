--
-- This updates a 1.39.35 database to 1.39.36
--
-- Make (EventId, FrameId) the primary key of Frames and drop the surrogate Id.
--
-- Frames is the largest table in the schema. Every query against it is
-- anchored on EventId and orders or ranges by FrameId (see zm_update-1.39.27),
-- and nothing looks a frame up by Id. Keeping Id meant a clustered index that
-- nothing reads plus a secondary EventId_FrameId_idx that every read uses,
-- each secondary entry also carrying a copy of Id. On a copy of a real table
-- the secondary index was half the table's size. With (EventId, FrameId) as
-- the clustered key that index goes away, and an event's rows sit together on
-- disk, so deleting an event is a range delete rather than scattered writes.
--
-- FrameId is the per-event frame counter, so (EventId, FrameId) is unique for
-- anything zmc writes. Duplicates are removed first anyway, keeping the
-- earliest row, because a single duplicate would make ADD PRIMARY KEY fail.
--
-- TimeStamp also loses ON UPDATE CURRENT_TIMESTAMP. It is the time the frame
-- was captured, and any UPDATE of a frame row was silently replacing it with
-- the time of the update.
--
-- AI_Detections.FrameId was a foreign key to Frames.Id. It becomes the
-- per-event frame number, the same thing Frames.FrameId, Stats.FrameId and
-- Event_Data.FrameId hold, converted from the old Id before Id is dropped.
-- It cannot be a composite foreign key to Frames: ON DELETE SET NULL would
-- have to null the NOT NULL EventId, and Frames rows are written in batches,
-- so a detection can be recorded before its frame row exists.
--
-- Note for large installs: this rebuilds the table and will take a while on a
-- Frames table with hundreds of millions of rows.
--

set @exist := (select count(*) from information_schema.columns where table_schema = database() and table_name = 'Frames' and column_name = 'Id');

set @fk := (select constraint_name from information_schema.referential_constraints where constraint_schema = database() and table_name = 'AI_Detections' and referenced_table_name = 'Frames' limit 1);
set @sqlstmt := if( @fk is not null, concat('ALTER TABLE `AI_Detections` DROP FOREIGN KEY `', @fk, '`'), "SELECT 'AI_Detections has no foreign key to Frames.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @sqlstmt := if( @exist > 0, 'UPDATE `AI_Detections` D JOIN `Frames` F ON F.`Id` = D.`FrameId` SET D.`FrameId` = F.`FrameId`', "SELECT 'AI_Detections.FrameId already converted.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @idx := (select count(*) from information_schema.statistics where table_schema = database() and table_name = 'AI_Detections' and index_name = 'AI_Detections_EventId_FrameId_idx');
set @sqlstmt := if( @idx = 0, 'ALTER TABLE `AI_Detections` MODIFY `FrameId` int(10) unsigned, ADD INDEX `AI_Detections_EventId_FrameId_idx` (`EventId`,`FrameId`)', "SELECT 'AI_Detections_EventId_FrameId_idx already exists.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @idx := (select count(*) from information_schema.statistics where table_schema = database() and table_name = 'AI_Detections' and index_name = 'AI_Detections_FrameId_idx');
set @sqlstmt := if( @idx > 0, 'DROP INDEX `AI_Detections_FrameId_idx` ON `AI_Detections`', "SELECT 'AI_Detections_FrameId_idx already removed.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @idx := (select count(*) from information_schema.statistics where table_schema = database() and table_name = 'AI_Detections' and index_name = 'AI_Detections_EventId_idx');
set @sqlstmt := if( @idx > 0, 'DROP INDEX `AI_Detections_EventId_idx` ON `AI_Detections`', "SELECT 'AI_Detections_EventId_idx already removed.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Removing duplicates must not take row locks across the whole table: an
-- unbatched self-join DELETE locks every row it scans and fails with
-- ERROR 1206 on a large Frames table, duplicates or not. So first look for
-- duplicates with a plain SELECT ... INTO, which is a non-locking read, and
-- only if there are any, delete them a range of events at a time so each
-- statement locks just that range.

DELIMITER //

DROP PROCEDURE IF EXISTS `zm_remove_duplicate_frames` //

CREATE PROCEDURE `zm_remove_duplicate_frames`()
BEGIN
  DECLARE v_start, v_max BIGINT UNSIGNED;
  DECLARE v_dups INT DEFAULT 0;

  SELECT COUNT(*) INTO v_dups FROM (SELECT 1 FROM `Frames` GROUP BY `EventId`, `FrameId` HAVING COUNT(*) > 1 LIMIT 1) AS d;
  IF v_dups > 0 THEN
    SELECT MIN(`EventId`), MAX(`EventId`) INTO v_start, v_max FROM `Frames`;
    WHILE v_start <= v_max DO
      DELETE F1 FROM `Frames` F1 JOIN `Frames` F2 ON F1.`EventId` = F2.`EventId` AND F1.`FrameId` = F2.`FrameId` AND F1.`Id` > F2.`Id`
        WHERE F1.`EventId` >= v_start AND F1.`EventId` < v_start + 100;
      SET v_start = v_start + 100;
    END WHILE;
  END IF;
END //

DELIMITER ;

SELECT IF(@exist > 0, 'Removing duplicate (EventId, FrameId) rows from Frames.', 'Frames.Id already removed.');
set @sqlstmt := if( @exist > 0, 'CALL zm_remove_duplicate_frames()', "SELECT 1");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DROP PROCEDURE IF EXISTS `zm_remove_duplicate_frames`;

SELECT IF(@exist > 0, 'Rebuilding Frames with (EventId, FrameId) as primary key. On a large Frames table this will take some time.', '');
set @sqlstmt := if( @exist > 0, 'ALTER TABLE `Frames`
  DROP PRIMARY KEY,
  DROP COLUMN `Id`,
  ADD PRIMARY KEY (`EventId`, `FrameId`),
  DROP INDEX `EventId_FrameId_idx`,
  MODIFY `TimeStamp` timestamp NOT NULL default CURRENT_TIMESTAMP', "SELECT 1");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
