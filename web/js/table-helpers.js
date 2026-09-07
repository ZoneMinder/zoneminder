'use strict';

// Helpers shared by the bootstrap-table views (events, console, log, frames,
// reports, snapshots, watch). Loaded as a plain browser script before the view
// scripts (web/skins/classic/includes/functions.php), so the functions below are
// globals by the time any of them runs. Also CommonJS-exported for node unit
// tests (tests/js/table-helpers.test.js).

// Tables whose ajax request was skipped because the page was hidden, waiting to
// be refreshed once it is shown again.
const tablesPendingVisibility = [];

// Decide whether to skip a bootstrap-table ajax request because the page is
// hidden. Returns true when the caller should return without issuing it.
//
// Skipping alone is not enough. Bootstrap-table calls its ajax function on init
// as well as on refresh, and a skipped request is never re-issued, so the table
// renders "No matching records found" over a result it never asked for. Nothing
// brings it back and the user has to refresh by hand (issue #5026). Recording
// the table here means it is refreshed the moment the page becomes visible.
//
// A page can be hidden for the whole of its load - opened in a background tab,
// restored, or simply behind another window - so this is not only about a tab
// the user switched away from later.
function deferTableRequestWhileHidden(table) {
  if (document.visibilityState !== 'hidden') return false;
  table.bootstrapTable('hideLoading');
  // A table can be deferred repeatedly (init, then auto-refresh ticks) and only
  // needs refreshing once.
  if (tablesPendingVisibility.indexOf(table) === -1) {
    tablesPendingVisibility.push(table);
  }
  return true;
}

// Refresh every table whose request was skipped while hidden. After a long
// hide the auth hash we were holding has expired, so wait for a fresh one
// rather than have every deferred table 403 (auth-helpers.js). whenAuthFresh is
// absent under node and on the unauthenticated views; refresh directly there.
function refreshTablesPendingVisibility() {
  if (document.visibilityState === 'hidden') return;
  // Drain before refreshing: refresh() calls the ajax function synchronously,
  // which would otherwise re-add the table while we are still iterating.
  const tables = tablesPendingVisibility.splice(0, tablesPendingVisibility.length);
  const refresh = function() {
    for (let i = 0; i < tables.length; i++) {
      tables[i].bootstrapTable('refresh');
    }
  };
  if (typeof whenAuthFresh === 'function') {
    whenAuthFresh(refresh);
  } else {
    refresh();
  }
}

if (typeof document !== 'undefined' && document.addEventListener) {
  document.addEventListener('visibilitychange', refreshTablesPendingVisibility);
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    deferTableRequestWhileHidden,
    refreshTablesPendingVisibility,
    tablesPendingVisibility,
  };
}
