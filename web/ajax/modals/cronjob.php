<?php
// The add/edit modal for Options -> Scheduled Tasks.
//
// An id of 0 is "add"; anything else loads that task. The form posts back to
// ?view=cron, which includes/actions/cron.php handles -- the same path the
// page used when this was an inline form, so validation and the crontab
// rebuild are unchanged.
if (!isset($_REQUEST['id'])) {
  ajaxError('Task Id Not Provided');
  return;
}

if (!canEdit('System')) return;

require_once('includes/CronJob.php');
require_once('includes/CronTab.php');
require_once('includes/Server.php');

$jid = validCardinal($_REQUEST['id']);
$job = $jid ? ZM\CronJob::find_one(array('Id'=>$jid)) : null;
if ($jid and !$job) return;
if (!$job) $job = new ZM\CronJob();

$frequency_options = array(
  'daily'     => translate('ScheduleEveryDay'),
  'weekdays'  => translate('ScheduleEveryWeekday'),
  'weekends'  => translate('ScheduleEveryWeekend'),
  'monday'    => translate('ScheduleEveryMonday'),
  'tuesday'   => translate('ScheduleEveryTuesday'),
  'wednesday' => translate('ScheduleEveryWednesday'),
  'thursday'  => translate('ScheduleEveryThursday'),
  'friday'    => translate('ScheduleEveryFriday'),
  'saturday'  => translate('ScheduleEverySaturday'),
  'sunday'    => translate('ScheduleEverySunday'),
);

$server_options = array('' => translate('CronAnyServer'));
foreach (ZM\Server::find() as $server) $server_options[$server->Id()] = $server->Name();

// Three states for the When row, and they want different things.
//
// Adding: nothing is set yet, so the pickers start somewhere sensible and the
// cron field starts matching them.
//
// Editing a schedule the pickers can draw: show it.
//
// Editing one they cannot, such as "*/5 * * * *": the cron field is opened so
// the real schedule is in front of whoever opened the task, and the pickers
// get a placeholder rather than a default. Defaulting them to "Every day at
// 03:00" beside a cron field reading "*/5 * * * *" would put two different
// answers on screen at once, with the wrong one the bigger and friendlier of
// the two. cronSpecFromPickers() refuses to build from the placeholder, so
// the field is left alone until a picker is deliberately changed.
$adding = !$job->Id();
$parts = cronToParts($job->Schedule());
if ($parts and !isset($frequency_options[$parts['frequency']])) $parts = null;
if ($adding) $parts = array('frequency'=>'daily', 'hour'=>3, 'minute'=>0);
else if (!$parts) $frequency_options = array('' => translate('CronScheduleAsCron')) + $frequency_options;
?>
<div class="modal fade" id="CronJobModal" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo $job->Id() ?
          translate('CronEditTask').' - '.validHtmlStr($job->Name()) :
          translate('CronAddTask') ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="cronJobModalForm" name="contentForm" method="post" action="?view=cron">
        <div class="modal-body">
<?php
          // The csrf key has to be put in by hand for a form built over ajax.
          echo getCSRFinputHTML();
?>
          <input type="hidden" name="view" value="cron"/>
          <input type="hidden" name="id" value="<?php echo $job->Id() ? validInt($job->Id()) : 0 ?>"/>
          <table class="table-sm">
            <tbody>
              <tr class="Name">
                <th scope="row"><?php echo translate('Name') ?></th>
                <td><input type="text" name="newJob[Name]" maxlength="64" size="40"
                      value="<?php echo validHtmlStr($job->Name()) ?>"/></td>
              </tr>
              <tr class="Command">
                <th scope="row"><?php echo translate('CronCommand') ?></th>
                <td>
                  <input type="text" name="newJob[Command]" size="60" required="required"
                    placeholder="/usr/local/bin/my-script.sh"
                    value="<?php echo validHtmlStr($job->Command()) ?>"/>
                  <div class="form-text text-muted"><?php echo translate('CronCommandHelp') ?></div>
                </td>
              </tr>
              <tr class="When">
                <th scope="row"><?php echo translate('CronWhen') ?></th>
                <td>
                  <?php echo translate('ScheduleEvery') ?>
                  <?php echo htmlSelect('scheduleFrequency', $frequency_options,
                        $parts ? $parts['frequency'] : '',
                        array('id'=>'scheduleFrequency')) ?>
                  <?php echo translate('ScheduleAt') ?>
                  <input id="scheduleTime" type="time" value="<?php echo $parts ?
                    sprintf('%02d:%02d', $parts['hour'], $parts['minute']) : '' ?>"/>
                  <?php # This is the control that is submitted: cron can say things the
                        # pickers cannot, and a task set that way has to survive editing.
                        # Behind a disclosure so it reads as a consequence of the pickers
                        # rather than a second, competing answer. ?>
                  <details class="mt-2"<?php echo $parts ? '' : ' open="open"' ?>>
                    <summary class="text-muted"><?php echo translate('CronAdvanced') ?></summary>
                    <input id="scheduleSpec" type="text" name="newJob[Schedule]" size="18"
                      required="required"
                      value="<?php echo validHtmlStr($job->Schedule() !== '' ?
                        $job->Schedule() : '0 3 * * *') ?>"/>
                    <div class="form-text text-muted"><?php echo translate('CronAdvancedHelp') ?></div>
                  </details>
                </td>
              </tr>
              <tr class="Server">
                <th scope="row"><?php echo translate('Server') ?></th>
                <td><?php echo htmlSelect('newJob[ServerId]', $server_options, $job->ServerId()) ?></td>
              </tr>
              <tr class="Enabled">
                <th scope="row"><?php echo translate('Enabled') ?></th>
                <td><input type="checkbox" name="newJob[Enabled]" value="1"<?php
                      echo $job->Enabled() ? ' checked="checked"' : '' ?>/></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button name="action" type="submit" class="btn btn-primary" value="save"><?php
            echo translate('Save') ?></button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php
            echo translate('Cancel') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
