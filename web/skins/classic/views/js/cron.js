'use strict';

// The scheduled tasks page: a list, with adding and editing done in a modal.
//
// Two jobs here. The first is building a cron spec out of the modal's two
// pickers; the second is opening the modal and wiring those pickers up once
// its html has arrived.
//
// The cron field is what gets submitted, because cron can say things the two
// pickers cannot and a task set that way must survive being edited. The
// pickers are a way to fill it in without knowing cron, so they write into it
// rather than replacing it.
//
// cronSpecFromPickers mirrors cronFromParts() in web/includes/CronTab.php. The
// server builds and validates the schedule again on save, so this is
// convenience, not enforcement -- but the two maps still have to agree or the
// field would be filled with something the server then rejects.
// tests/js/cron-schedule.test.js checks them against each other.

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

// Everything below touches the DOM. Guarded so the pure part above can be
// required by the tests, which have no window.
if (typeof window !== 'undefined') {
  /**
   * Keep the modal's cron field in step with its pickers.
   *
   * Called after the modal html is inserted rather than at page load: the
   * controls do not exist until then, and a fresh set arrives every time the
   * modal is opened.
   */
  function bindSchedulePickers() {
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
  }

  /**
   * Open the add/edit modal for a task.
   * @param {number} jid the task's Id, or 0 to add a new one
   */
  function getCronJobModal(jid) {
    $j.getJSON(thisUrl + '?request=modal&modal=cronjob&id=' + jid)
        .done(function(data) {
          insertModalHtml('CronJobModal', data.html);
          bindSchedulePickers();
          $j('#CronJobModal').modal('show');
        })
        .fail(logAjaxFail);
  }

  function enableCronJobModal() {
    $j('.cronjobCol').click(function(evt) {
      evt.preventDefault();
      getCronJobModal($j(this).data('jid'));
    });
    $j('#NewCronJobBtn').click(function(evt) {
      evt.preventDefault();
      getCronJobModal(0);
    });
  }

  $j(document).ready(function() {
    if (canEdit.System) enableCronJobModal();

    // Deleting a task stops it running, so it is worth a question. On the form
    // rather than the button so that submitting with the keyboard asks too.
    $j('form[name="cronForm"]').submit(function(evt) {
      if (!confirm(confirmDeleteCronString)) evt.preventDefault();
    });
  });
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = {CRON_FREQUENCY_DOW, cronSpecFromPickers};
}
