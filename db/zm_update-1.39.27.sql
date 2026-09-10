--
-- This updates a 1.39.26 database to 1.39.27
--
-- Replace the three secondary indexes on Frames with one on (EventId, FrameId).
--
-- Every query against Frames is anchored on EventId, and nearly all of them
-- order or range by FrameId within the event:
--
--   zm_eventstream.cpp, zma.cpp    WHERE EventId=? ORDER BY FrameId ASC
--   includes/Event.php, status.php WHERE EventId=? AND FrameId </> ? ORDER BY FrameId LIMIT 1
--   zmfilter.pl                    WHERE EventId=? AND Type=? ORDER BY FrameId
--   Event.pm, zmaudit.pl           max(FrameId), max(TimeStamp), sum(Score) WHERE EventId=?
--
-- The plain EventId index left every one of those to a filesort, which for the
-- LIMIT 1 prev/next-frame lookups on the playback path meant reading and
-- sorting an entire event's frames to return one row. The composite index
-- serves the ordering directly and answers max(FrameId) as an index lookup.
--
-- Nothing filters or sorts on Type or TimeStamp without EventId, so neither
-- index was ever used for reading. Type is a three-value enum, which cannot be
-- selective in any case. Both only cost insert time on the highest-insert-rate
-- table in the schema, plus space, plus work on every DELETE ... WHERE EventId.
--
-- Ordering matters here: the composite index is added first so that its
-- leftmost prefix covers EventId for any install still carrying a foreign key
-- on Frames.EventId (added in 1.35.11, dropped in 1.37.31 only when it was
-- named Frames_ibfk_1). Dropping EventId_idx first would fail on those.
--
-- Note for large installs: the ADD INDEX is an InnoDB in-place build and does
-- not block writes, but on a Frames table with hundreds of millions of rows it
-- will take a while. The three drops are effectively instant.
--

SELECT 'Adding EventId_FrameId_idx to Frames. On a large Frames table this will take some time.';

set @exist := (select count(*) from information_schema.statistics where table_schema = database() and table_name = 'Frames' and index_name = 'EventId_FrameId_idx');
set @sqlstmt := if( @exist = 0, 'ALTER TABLE `Frames` ADD INDEX `EventId_FrameId_idx` (`EventId`,`FrameId`)', "SELECT 'EventId_FrameId_idx INDEX already exists on Frames.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_schema = database() and table_name = 'Frames' and index_name = 'EventId_idx');
set @sqlstmt := if( @exist > 0, 'DROP INDEX `EventId_idx` ON `Frames`', "SELECT 'EventId_idx INDEX is already removed from Frames.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_schema = database() and table_name = 'Frames' and index_name = 'Type');
set @sqlstmt := if( @exist > 0, 'DROP INDEX `Type` ON `Frames`', "SELECT 'Type INDEX is already removed from Frames.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

set @exist := (select count(*) from information_schema.statistics where table_schema = database() and table_name = 'Frames' and index_name = 'TimeStamp');
set @sqlstmt := if( @exist > 0, 'DROP INDEX `TimeStamp` ON `Frames`', "SELECT 'TimeStamp INDEX is already removed from Frames.'");
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
