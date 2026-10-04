'use strict';

// Keeps the cron field on the scheduled tasks page in step with the pickers
// above it.
//
// The field is what gets submitted, because cron can say things the two
// pickers cannot and a task set that way must survive being edited. The
// pickers are a way to fill it in without knowing cron, so they write into it
// rather than replacing it.
//
// This mirrors cronFromParts() in web/includes/CronTab.php. The server builds
// and validates the schedule again on save, so this is convenience, not
// enforcement -- but the two maps still have to agree or the field would be
// filled with something the server then rejects. tests/js/cron-schedule.test.js
// checks them against each other.

// Frequency to cron's day-of-week field. Cron numbers Sunday 0 to Saturday 6.
const CRON_FREQUENCY_DOW = {
  daily: '*',
  weekdays: '1-5',
  weekends: '0,6',
  sunday: '0',
  monday: '1',
  tuesday: '2',
  wednesday: '3',
  thursday: '4',
  friday: '5',
  saturday: '6',
};

/**
 * Build a five field cron spec from a frequency and a time of day.
 * @param {string} frequency one of CRON_FREQUENCY_DOW's keys
 * @param {string} time "HH:MM", as <input type="time"> gives it
 * @return {?string} the spec, or null if the parts do not describe a time
 */
function cronSpecFromPickers(frequency, time) {
  const dow = CRON_FREQUENCY_DOW[frequency];
  if (dow === undefined) return null;

  const match = /^(\d{1,2}):(\d{2})(?::\d{2})?$/.exec(String(time));
  if (!match) return null;
  const hour = parseInt(match[1], 10);
  const minute = parseInt(match[2], 10);
  if (!isFinite(hour) || !isFinite(minute)) return null;
  if ((hour < 0) || (hour > 23) || (minute < 0) || (minute > 59)) return null;

  return minute + ' ' + hour + ' * * ' + dow;
}

// Guarded so the pure part above can be required by the tests, which have no
// DOM and no window.
if (typeof window !== 'undefined') {
  window.addEventListener('DOMContentLoaded', function initCronPickers() {
    const frequency = document.getElementById('scheduleFrequency');
    const time = document.getElementById('scheduleTime');
    const spec = document.getElementById('scheduleSpec');
    if (!frequency || !time || !spec) return;

    function sync() {
      const built = cronSpecFromPickers(frequency.value, time.value);
      // Leave whatever is in the field if the pickers cannot describe it; the
      // operator may be part way through typing a time.
      if (built !== null) spec.value = built;
    }

    frequency.addEventListener('change', sync);
    time.addEventListener('change', sync);
  });
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = {CRON_FREQUENCY_DOW, cronSpecFromPickers};
}
