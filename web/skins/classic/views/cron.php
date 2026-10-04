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

      <fieldset class="mb-4">
        <legend class="h5"><?php echo $editing->Id() ?
          translate('CronEditTask') : translate('CronAddTask') ?></legend>

        <form name="cronForm" method="post" action="?view=cron">
          <input type="hidden" name="view" value="cron"/>
          <input type="hidden" name="action" value="save"/>
          <input type="hidden" name="id" value="<?php echo validHtmlStr($editing->Id()) ?>"/>

          <div class="form-group row">
            <label for="jobName" class="col-sm-2 col-form-label"><?php echo translate('Name') ?></label>
            <div class="col-sm-6">
              <input class="form-control" id="jobName" type="text" name="newJob[Name]" maxlength="64"
                value="<?php echo validHtmlStr($editing->Name()) ?>"/>
            </div>
          </div>

          <div class="form-group row">
            <label for="jobCommand" class="col-sm-2 col-form-label"><?php echo translate('CronCommand') ?></label>
            <div class="col-sm-10">
              <input class="form-control" id="jobCommand" type="text" name="newJob[Command]" required="required"
                placeholder="/usr/local/bin/my-script.sh"
                value="<?php echo validHtmlStr($editing->Command()) ?>"/>
              <small class="form-text text-muted"><?php echo translate('CronCommandHelp') ?></small>
            </div>
          </div>

          <div class="form-group row">
            <label for="scheduleFrequency" class="col-sm-2 col-form-label"><?php echo translate('CronWhen') ?></label>
            <div class="col-sm-10">
              <div class="form-inline">
                <span class="mr-2"><?php echo translate('ScheduleEvery') ?></span>
                <?php echo htmlSelect('scheduleFrequency', $frequency_options,
                      $editing_parts ? $editing_parts['frequency'] : 'daily',
                      array('id'=>'scheduleFrequency', 'class'=>'form-control mr-2')) ?>
                <span class="mr-2"><?php echo translate('ScheduleAt') ?></span>
                <input id="scheduleTime" class="form-control" type="time" value="<?php echo $editing_parts ?
                  sprintf('%02d:%02d', $editing_parts['hour'], $editing_parts['minute']) : '03:00' ?>"/>
              </div>
              <?php # The cron spec is what gets stored and what cron reads, so it is
                    # always present and always submitted. It is tucked behind a
                    # disclosure because it is a consequence of the pickers above, not
                    # a second way of saying the same thing: showing the two side by
                    # side invited the question of which one wins. ?>
              <details class="mt-2"<?php echo $editing_parts ? '' : ' open="open"' ?>>
                <summary class="text-muted"><?php echo translate('CronAdvanced') ?></summary>
                <div class="form-inline mt-2">
                  <input class="form-control" id="scheduleSpec" type="text" name="newJob[Schedule]"
                    size="18" required="required"
                    value="<?php echo validHtmlStr($editing->Schedule() !== '' ? $editing->Schedule() : '0 3 * * *') ?>"/>
                </div>
                <small class="form-text text-muted"><?php echo translate('CronAdvancedHelp') ?></small>
              </details>
            </div>
          </div>

          <div class="form-group row">
            <label for="jobServer" class="col-sm-2 col-form-label"><?php echo translate('Server') ?></label>
            <div class="col-sm-4">
              <?php echo htmlSelect('newJob[ServerId]', $server_options, $editing->ServerId(),
                    array('id'=>'jobServer', 'class'=>'form-control')) ?>
            </div>
          </div>

          <div class="form-group row">
            <div class="col-sm-2"><?php echo translate('Enabled') ?></div>
            <div class="col-sm-10">
              <input id="jobEnabled" type="checkbox" name="newJob[Enabled]" value="1"<?php
                echo $editing->Enabled() ? ' checked="checked"' : '' ?>/>
            </div>
          </div>

          <div class="form-group row">
            <div class="col-sm-10 offset-sm-2">
              <button type="submit" class="btn btn-primary"><?php echo translate('Save') ?></button>
<?php if ($editing->Id()) { ?>
              <a class="btn btn-secondary ml-2" href="?view=cron"><?php echo translate('Cancel') ?></a>
<?php } ?>
            </div>
          </div>
        </form>
      </fieldset>

      <form name="cronListForm" method="post" action="?view=cron">
        <input type="hidden" name="view" value="cron"/>
        <input type="hidden" name="action" value="delete"/>
        <table class="table table-sm table-striped">
          <thead class="thead-highlight">
            <tr>
              <th class="colMark"><?php echo translate('Mark') ?></th>
              <th><?php echo translate('Name') ?></th>
              <th><?php echo translate('CronWhen') ?></th>
              <th><?php echo translate('CronCommand') ?></th>
              <th><?php echo translate('Server') ?></th>
              <th><?php echo translate('Enabled') ?></th>
            </tr>
          </thead>
          <tbody>
<?php
if (!count($jobs)) {
?>
            <tr><td colspan="6" class="text-muted"><?php echo translate('CronNoTasks') ?></td></tr>
<?php
}
foreach ($jobs as $job) {
  $parts = cronToParts($job->Schedule());
  if ($parts and isset($frequency_options[$parts['frequency']])) {
    // Read back as the sentence the pickers would have produced, with the
    // spec itself on hover for anyone who wants to check it.
    $when = translate('ScheduleEvery').' '.$frequency_options[$parts['frequency']].' '.
            translate('ScheduleAt').' '.sprintf('%02d:%02d', $parts['hour'], $parts['minute']);
  } else {
    $when = $job->Schedule();
  }
?>
            <tr>
              <td class="colMark"><input type="checkbox" name="markIds[]" value="<?php echo $job->Id() ?>"/></td>
              <td><a href="?view=cron&amp;id=<?php echo $job->Id() ?>"><?php
                  echo validHtmlStr($job->Name()) ?></a></td>
              <td title="<?php echo validHtmlStr($job->Schedule()) ?>"><?php echo validHtmlStr($when) ?></td>
              <td><code><?php echo validHtmlStr($job->Command()) ?></code></td>
              <td><?php echo $job->ServerId() ?
                    validHtmlStr(isset($server_options[$job->ServerId()]) ?
                      $server_options[$job->ServerId()] : $job->ServerId()) :
                    translate('CronAnyServer') ?></td>
              <td><?php echo $job->Enabled() ? translate('Yes') : translate('No') ?></td>
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
