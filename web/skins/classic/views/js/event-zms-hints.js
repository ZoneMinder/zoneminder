//
// ZoneMinder event view: neighbour hints for zms
//
// UMD-pattern module: exposed as window.ZM_EventZmsHints in browsers and via
// module.exports in Node for unit testing.
//
// When zms streams an event it moves on to the next one by itself. Only the
// page knows the filter and sort the user is viewing, so the page sends zms
// the next and prev event in that order (CMD_SET_EVENT), each bound to the
// event it was computed for (refs #4186). This keeps track of what to send and
// when:
//
// - Hints go out only once zms has answered a status query for their event, so
//   they are not lost to a zms that has not opened its socket yet, or to one
//   that has already moved on.
// - A hint that did not reach zms is sent again on the next status reply.
//   Each send is numbered, so a late reply to an earlier send cannot settle a
//   later one. A send waits for the replies to the one before it: the commands
//   are separate requests, and could otherwise reach zms out of order and leave
//   it with the older hint.
// - A restarted zms has none of them, so they are sent again.
// - The neighbours of the event zms is playing are looked up again when the
//   last lookup failed or was for another event, at most every
//   LOOKUP_RETRY_MS, and one request at a time. Lookups are numbered, so the
//   reply to one given up on cannot overwrite a newer one's.
// - A boundary (no next event) is asked for again every BOUNDARY_REFRESH_MS
//   while it is in the direction of play, since an event may have been
//   recorded since. So are any neighbours while zms is paused: it stops at the
//   end of an event whose next one has been deleted, and resumes when sent a
//   different one.
//
(function(global) {
  'use strict';

  const BOUNDARY_REFRESH_MS = 5000;
  const LOOKUP_RETRY_MS = 2000;
  // A lookup with no reply after this long is taken as lost. Well inside the
  // 10s zms waits at the end of an event, so a retry still reaches it.
  const LOOKUP_TIMEOUT_MS = 5000;

  function create() {
    // {src, next, prev, fetched, state: 'unsent'|'sending'|'sent', batch, inflight, failed}
    let hints = null;
    // The last neighbour lookup: {src, at, pending, id}
    let lookup = null;
    let lookups = 0;
    let batches = 0;
    // Batch number -> commands of it sent to this zms and not yet answered.
    let unanswered = new Map();

    return {
      // Neighbours from getNearEvents for event src, fetched at time now.
      set: function(src, next, prev, now) {
        if (hints && hints.src == src && hints.next == next && hints.prev == prev) {
          hints.fetched = now;
          return;
        }
        hints = {src: src, next: next, prev: prev, fetched: now, state: 'unsent', batch: 0, inflight: 0, failed: false};
      },

      // The commands to send now that zms reports playing event current: both
      // directions, once, if the hints are for that event and not yet sent.
      // Each carries the batch number to pass back to response().
      take: function(current) {
        if (!hints || hints.src != current || hints.state != 'unsent' || unanswered.size) return [];
        hints.state = 'sending';
        hints.batch = ++batches;
        unanswered.set(hints.batch, 2);
        hints.inflight = 2;
        hints.failed = false;
        return [
          {dir: 0, eventid: hints.next, srcid: hints.src, batch: hints.batch},
          {dir: 1, eventid: hints.prev, srcid: hints.src, batch: hints.batch},
        ];
      },

      // The reply to one command from take(). delivered is false when it did
      // not reach zms; both are then sent again.
      response: function(batch, delivered) {
        if (unanswered.has(batch)) {
          const left = unanswered.get(batch) - 1;
          if (left > 0) unanswered.set(batch, left);
          else unanswered.delete(batch);
        }
        if (!hints || hints.batch != batch || hints.state != 'sending') return;
        if (!delivered) hints.failed = true;
        if (--hints.inflight > 0) return;
        hints.state = hints.failed ? 'unsent' : 'sent';
      },

      // zms was restarted: the new process has none of the hints. Replies to
      // sends to the old process no longer count, and need not be waited for:
      // those commands were sent to the old process before this one started.
      reset: function() {
        unanswered = new Map();
        if (hints) {
          hints.state = 'unsent';
          hints.batch = 0;
        }
      },

      // Whether to look up current's neighbours now, while playing in the
      // given direction, or paused.
      needsLookup: function(current, reverse, now, paused) {
        const same = lookup && lookup.src == current;
        if (same && lookup.pending && now - lookup.at < LOOKUP_TIMEOUT_MS) return false;
        if (!hints || hints.src != current) {
          // None yet for this event, or the last attempt failed.
          return !same || now - lookup.at >= LOOKUP_RETRY_MS;
        }
        // The filter had nothing after it in the direction of play when last
        // asked, or zms is paused, maybe on a deleted neighbour; a while ago.
        if (!paused && (reverse ? hints.prev : hints.next) != 0) return false;
        const asked = same ? Math.max(lookup.at, hints.fetched) : hints.fetched;
        return now - asked >= BOUNDARY_REFRESH_MS;
      },

      // A lookup for src's neighbours was sent at time now. Returns its
      // number, for isLatestLookup() and lookupDone().
      lookingUp: function(src, now) {
        lookup = {src: src, at: now, pending: true, id: ++lookups};
        return lookup.id;
      },

      // Whether lookup id is the last one sent. A reply to an earlier one is
      // older than what the last one will bring, and is dropped.
      isLatestLookup: function(id) {
        return lookup != null && lookup.id == id;
      },

      // Lookup id got a reply or failed.
      lookupDone: function(id) {
        if (lookup && lookup.id == id) lookup.pending = false;
      },
    };
  }

  const api = {
    create: create,
    BOUNDARY_REFRESH_MS: BOUNDARY_REFRESH_MS,
    LOOKUP_RETRY_MS: LOOKUP_RETRY_MS,
    LOOKUP_TIMEOUT_MS: LOOKUP_TIMEOUT_MS,
  };

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = api;
  } else {
    global.ZM_EventZmsHints = api;
  }
})(typeof window !== 'undefined' ? window : globalThis);
