--
-- Events_Lock: advisory locks over events for filters with LockRows set.
--
-- Replaces the SELECT ... FOR UPDATE [SKIP LOCKED] that LockRows filters used
-- to run over their whole result set. That held every row lock the batch took -
-- Events, Events_Hour/Day/Week/Month, Event_Summaries, Storage - until the
-- batch committed, which deadlocked two filters against each other and blocked
-- zmc from opening new events on any monitor the filter had touched.
--
-- Claiming an event is now a single autocommitted INSERT of one row in here, so
-- no lock is held while the filter works on the event.
--
-- No foreign key to Events on purpose: it would make every event delete take a
-- lock in this table, which is the coupling being removed. Rows left behind for
-- deleted events are harmless and expire.
--
-- Idempotent, so re-running is a no-op.
--

CREATE TABLE IF NOT EXISTS `Events_Lock` (
  `EventId`   BIGINT unsigned NOT NULL,
  `LockedBy`  varchar(64) NOT NULL,
  `LockedAt`  datetime NOT NULL,
  `ExpiresAt` datetime NOT NULL,
  PRIMARY KEY (`EventId`),
  KEY `Events_Lock_ExpiresAt_idx` (`ExpiresAt`)
) ENGINE=InnoDB;
