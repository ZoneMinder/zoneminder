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
// - A restarted zms has none of them, so they are sent again.
// - A boundary (no next event) is asked for again every BOUNDARY_REFRESH_MS
//   while it is in the direction of play, since an event may have been
//   recorded since.
//
(function(global) {
  'use strict';

  const BOUNDARY_REFRESH_MS = 5000;

  function create() {
    // {src, next, prev, fetched, state: 'unsent'|'sending'|'sent', inflight, failed}
    let hints = null;

    return {
      // Neighbours from getNearEvents for event src, fetched at time now.
      set: function(src, next, prev, now) {
        if (hints && hints.src == src && hints.next == next && hints.prev == prev) {
          hints.fetched = now;
          return;
        }
        hints = {src: src, next: next, prev: prev, fetched: now, state: 'unsent', inflight: 0, failed: false};
      },

      // The commands to send now that zms reports playing event current: both
      // directions, once, if the hints are for that event and not yet sent.
      take: function(current) {
        if (!hints || hints.src != current || hints.state != 'unsent') return [];
        hints.state = 'sending';
        hints.inflight = 2;
        hints.failed = false;
        return [
          {dir: 0, eventid: hints.next, srcid: hints.src},
          {dir: 1, eventid: hints.prev, srcid: hints.src},
        ];
      },

      // The reply to one command from take(). delivered is false when it did
      // not reach zms; both are then sent again.
      response: function(src, delivered) {
        if (!hints || hints.src != src || hints.state != 'sending') return;
        if (!delivered) hints.failed = true;
        if (--hints.inflight > 0) return;
        hints.state = hints.failed ? 'unsent' : 'sent';
      },

      // zms was restarted: the new process has none of the hints.
      reset: function() {
        if (hints) hints.state = 'unsent';
      },

      // Whether to ask again for current's neighbours: the filter had nothing
      // after it in the direction of play when last asked, a while ago.
      needsRefresh: function(current, reverse, now) {
        if (!hints || hints.src != current) return false;
        if ((reverse ? hints.prev : hints.next) != 0) return false;
        return now - hints.fetched >= BOUNDARY_REFRESH_MS;
      },

      // A refresh was requested at time now; don't ask again until it is due.
      refreshing: function(now) {
        if (hints) hints.fetched = now;
      },
    };
  }

  const api = {
    create: create,
    BOUNDARY_REFRESH_MS: BOUNDARY_REFRESH_MS,
  };

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = api;
  } else {
    global.ZM_EventZmsHints = api;
  }
})(typeof window !== 'undefined' ? window : globalThis);
