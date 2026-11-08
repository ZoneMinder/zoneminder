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
// This page is the list of tasks. Adding and editing one happens in a modal,
// built by web/ajax/modals/cronjob.php, which posts back here so that
// includes/actions/cron.php does the validating and the crontab rebuild.

if (!canView('System')) {
  $view = 'error';
  return;
}

require_once('includes/CronJob.php');
require_once('includes/CronTab.php');
require_once('includes/Server.php');

$canEdit = canEdit('System');

$jobs = ZM\CronJob::find(array(), array('order'=>'Id'));

// Used to read a schedule back as the sentence the pickers would have made.
// The modal has its own copy because it is rendered on its own; this one is
// for the When column.
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

$server_names = array();
foreach (ZM\Server::find() as $server) $server_names[$server->Id()] = $server->Name();

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
        <input type="hidden" name="action" value="delete"/>
        <div id="contentButtons">
          <button type="button" id="NewCronJobBtn" title="<?php echo translate('CronAddTask') ?>"<?php
            echo $canEdit ? '' : ' disabled="disabled"' ?>>
            <i class="material-icons">add_circle</i>
            <span class="text">&nbsp;<?php echo translate('CronAddTask') ?></span>
          </button>
          <button type="submit" class="btn-danger" name="deleteBtn" value="Delete"
            title="<?php echo translate('Delete') ?>" disabled="disabled">
            <i class="material-icons">delete</i>
            <span class="text">&nbsp;<?php echo translate('Delete') ?></span>
          </button>
        </div>
        <table id="contentTable" class="table table-sm table-striped">
          <thead class="thead-highlight">
            <tr>
              <th class="colMark"><?php echo translate('Mark') ?></th>
              <th class="colName"><?php echo translate('Name') ?></th>
              <th class="colWhen"><?php echo translate('CronWhen') ?></th>
              <th class="colCommand"><?php echo translate('CronCommand') ?></th>
              <th class="colServer"><?php echo translate('Server') ?></th>
              <th class="colEnabled"><?php echo translate('Enabled') ?></th>
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
  $job_opt = 'class="cronjobCol" data-jid="'.$job->Id().'"';
?>
            <tr>
              <td class="colMark"><input type="checkbox" name="markIds[]" value="<?php echo $job->Id() ?>"
                  data-on-click-this="configureDeleteButton"<?php
                  echo $canEdit ? '' : ' disabled="disabled"' ?>/></td>
              <td class="colName"><?php echo makeLink('#', validHtmlStr($job->Name()), $canEdit, $job_opt) ?></td>
              <td class="colWhen" title="<?php echo validHtmlStr($job->Schedule()) ?>"><?php
                  echo makeLink('#', validHtmlStr($when), $canEdit, $job_opt) ?></td>
              <td class="colCommand"><code><?php echo validHtmlStr($job->Command()) ?></code></td>
              <td class="colServer"><?php echo $job->ServerId() ?
                    validHtmlStr(isset($server_names[$job->ServerId()]) ?
                      $server_names[$job->ServerId()] : $job->ServerId()) :
                    translate('CronAnyServer') ?></td>
              <td class="colEnabled"><?php echo $job->Enabled() ? translate('Yes') : translate('No') ?></td>
            </tr>
<?php
} // end foreach job
?>
          </tbody>
        </table>
      </form>
    </div>
  </div>
<?php xhtmlFooter() ?>
</body>
</html>
