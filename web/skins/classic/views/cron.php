<?php
//
// ZoneMinder scheduled tasks view
// Copyright (C) 2026 ZoneMinder LLC
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

if (!canView('System')) {
  $view = 'error';
  return;
}

require_once('includes/CronJob.php');
require_once('includes/CronTab.php');
require_once('includes/Server.php');

$jobs = ZM\CronJob::find(array(), array('order'=>'Id'));
$editing = (isset($_REQUEST['id']) and $_REQUEST['id'] !== '')
  ? ZM\CronJob::find_one(array('Id'=>validInt($_REQUEST['id']))) : null;
if (!$editing) $editing = new ZM\CronJob();

// The pickers from the monitor action editor, offered here too. A task the
// pickers cannot draw keeps its own schedule and is shown as cron.
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

$editing_parts = cronToParts($editing->Schedule());
if ($editing_parts and !isset($frequency_options[$editing_parts['frequency']]))
  $editing_parts = null;

$focusWindow = true;
xhtmlHeaders(__FILE__, translate('ScheduledTasks'));
?>
<body>
  <div id="page">
    <?php echo getNavBarHTML() ?>
    <div id="content" class="container-fluid p-3">
      <h2><?php echo translate('ScheduledTasks') ?></h2>
      <p class="form-text"><?php echo translate('ScheduledTasksHelp') ?></p>
<?php
if (!empty($_REQUEST['error'])) {
  echo '<div class="alert alert-danger">'.validHtmlStr($_REQUEST['error']).'</div>';
}
?>

      <form name="cronForm" method="post" action="?view=cron">
        <input type="hidden" name="view" value="cron"/>
        <input type="hidden" name="action" value="save"/>
        <input type="hidden" name="id" value="<?php echo validHtmlStr($editing->Id()) ?>"/>

        <div class="form-row">
          <div class="form-group col-md-3">
            <label for="jobName"><?php echo translate('Name') ?></label>
            <input class="form-control" id="jobName" type="text" name="newJob[Name]" maxlength="64"
              value="<?php echo validHtmlStr($editing->Name()) ?>"/>
          </div>
          <div class="form-group col-md-5">
            <label for="jobCommand"><?php echo translate('CronCommand') ?></label>
            <input class="form-control" id="jobCommand" type="text" name="newJob[Command]" required="required"
              placeholder="/usr/local/bin/my-script.sh"
              value="<?php echo validHtmlStr($editing->Command()) ?>"/>
            <small class="form-text"><?php echo translate('CronCommandHelp') ?></small>
          </div>
          <div class="form-group col-md-2">
            <label for="jobServer"><?php echo translate('Server') ?></label>
            <?php echo htmlSelect('newJob[ServerId]', $server_options, $editing->ServerId(),
                  array('id'=>'jobServer', 'class'=>'form-control')) ?>
          </div>
          <div class="form-group col-md-2">
            <label for="jobEnabled"><?php echo translate('Enabled') ?></label><br/>
            <input id="jobEnabled" type="checkbox" name="newJob[Enabled]" value="1"<?php
              echo $editing->Enabled() ? ' checked="checked"' : '' ?>/>
          </div>
        </div>

        <div class="form-row align-items-end">
          <div class="form-group col-md-auto">
            <label><?php echo translate('ActionSchedule') ?></label><br/>
            <?php echo translate('ScheduleEvery') ?>
            <?php echo htmlSelect('scheduleFrequency', $frequency_options,
                  $editing_parts ? $editing_parts['frequency'] : 'daily',
                  array('id'=>'scheduleFrequency')) ?>
            <?php echo translate('ScheduleAt') ?>
            <input id="scheduleTime" type="time" value="<?php echo $editing_parts ?
              sprintf('%02d:%02d', $editing_parts['hour'], $editing_parts['minute']) : '03:00' ?>"/>
          </div>
          <div class="form-group col-md-auto">
            <label for="scheduleSpec"><?php echo translate('CronAdvanced') ?></label>
            <input class="form-control" id="scheduleSpec" type="text" name="newJob[Schedule]" size="16"
              required="required"
              value="<?php echo validHtmlStr($editing->Schedule() !== '' ? $editing->Schedule() : '0 3 * * *') ?>"/>
            <small class="form-text"><?php echo translate('CronAdvancedHelp') ?></small>
          </div>
          <div class="form-group col-md-auto">
            <button type="submit" class="btn btn-primary"><?php echo translate('Save') ?></button>
<?php if ($editing->Id()) { ?>
            <a class="btn btn-secondary" href="?view=cron"><?php echo translate('Cancel') ?></a>
<?php } ?>
          </div>
        </div>
      </form>

      <hr/>
      <form name="cronListForm" method="post" action="?view=cron">
        <input type="hidden" name="view" value="cron"/>
        <input type="hidden" name="action" value="delete"/>
        <table class="table table-sm table-striped">
          <thead>
            <tr>
              <th>&nbsp;</th>
              <th><?php echo translate('Name') ?></th>
              <th><?php echo translate('ActionSchedule') ?></th>
              <th><?php echo translate('CronCommand') ?></th>
              <th><?php echo translate('Server') ?></th>
              <th><?php echo translate('Enabled') ?></th>
              <th>&nbsp;</th>
            </tr>
          </thead>
          <tbody>
<?php
if (!count($jobs)) {
?>
            <tr><td colspan="7" class="text-muted"><?php echo translate('CronNoTasks') ?></td></tr>
<?php
}
foreach ($jobs as $job) {
  $parts = cronToParts($job->Schedule());
  if ($parts and isset($frequency_options[$parts['frequency']])) {
    // Read it back as the sentence the pickers would have produced.
    $when = translate('ScheduleEvery').' '.$frequency_options[$parts['frequency']].' '.
            translate('ScheduleAt').' '.sprintf('%02d:%02d', $parts['hour'], $parts['minute']);
  } else {
    $when = $job->Schedule();
  }
?>
            <tr>
              <td><input type="checkbox" name="markIds[]" value="<?php echo $job->Id() ?>"/></td>
              <td><?php echo validHtmlStr($job->Name()) ?></td>
              <td><?php echo validHtmlStr($when) ?></td>
              <td><code><?php echo validHtmlStr($job->Command()) ?></code></td>
              <td><?php echo $job->ServerId() ?
                    validHtmlStr($server_options[$job->ServerId()] ?? $job->ServerId()) :
                    translate('CronAnyServer') ?></td>
              <td><?php echo $job->Enabled() ? translate('Yes') : translate('No') ?></td>
              <td><a href="?view=cron&amp;id=<?php echo $job->Id() ?>"><?php echo translate('Edit') ?></a></td>
            </tr>
<?php
} // end foreach job
?>
          </tbody>
        </table>
<?php if (count($jobs)) { ?>
        <button type="submit" class="btn btn-danger"
          onclick="return confirm('<?php echo translate('CronConfirmDelete') ?>');"><?php
          echo translate('Delete') ?></button>
<?php } ?>
      </form>
    </div>
  </div>
<?php xhtmlFooter() ?>
</body>
</html>
