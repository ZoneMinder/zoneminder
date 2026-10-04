<?php
//
// ZoneMinder web action file
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

// Scheduled task edit actions.
//
// These rows become lines in the web user's crontab and run as that user, so
// this is a System-level privilege and is gated as one. That is the same trust
// Filters' AutoExecuteCmd has carried for years, not a new one, but it is
// worth being plain about: anyone who can edit here can run commands as the
// web user.
if (!canEdit('System')) {
  ZM\Warning('Need System permissions to manage scheduled tasks');
  return;
}

require_once('includes/CronJob.php');
require_once('includes/CronTab.php');
require_once('includes/Command.php');

$cron_error = '';

if ($action == 'save') {
  $posted = isset($_REQUEST['newJob']) && is_array($_REQUEST['newJob'])
    ? $_REQUEST['newJob'] : array();

  $schedule = isset($posted['Schedule']) ? trim($posted['Schedule']) : '';
  $command = isset($posted['Command']) ? trim($posted['Command']) : '';
  $name = isset($posted['Name']) ? trim($posted['Name']) : '';

  // Both are checked here rather than only in the browser: this is the last
  // point before the strings become a crontab line.
  if (!cronScheduleIsValid($schedule)) {
    $cron_error = translate('CronBadSchedule');
  } else if (!cronCommandIsValid($command)) {
    $cron_error = translate('CronBadCommand');
  } else if (!commandAllowedByConfig($command)) {
    $cron_error = commandRefusedMessage();
  } else {
    $values = array(
      'Name'     => $name !== '' ? $name : $command,
      'Enabled'  => empty($posted['Enabled']) ? 0 : 1,
      'Schedule' => $schedule,
      'Command'  => $command,
      'ServerId' => empty($posted['ServerId']) ? null : validInt($posted['ServerId']),
    );

    $id = empty($_REQUEST['id']) ? 0 : validInt($_REQUEST['id']);
    $job = $id ? ZM\CronJob::find_one(array('Id'=>$id)) : new ZM\CronJob();
    if (!$job) $job = new ZM\CronJob();

    if ($job->Id()) {
      $job->save($values);
      ZM\Info('Updated scheduled task '.$job->Id().': '.$schedule.' '.$command);
    } else if ($job->insert($values)) {
      ZM\Info('Added scheduled task '.$job->Id().': '.$schedule.' '.$command);
    } else {
      $cron_error = $job->get_last_error();
    }
  }
} else if ($action == 'delete') {
  if (!empty($_REQUEST['markIds'])) {
    foreach ($_REQUEST['markIds'] as $id) {
      $id = validInt($id);
      if (!$id) continue;
      ZM\Info('Deleting scheduled task '.$id);
      dbQuery('DELETE FROM CronJobs WHERE Id=?', array($id));
    }
  }
}

if (in_array($action, array('save', 'delete')) and ($cron_error === '')) {
  // The crontab is a projection of the table, rebuilt whenever the table
  // changes, so the two cannot drift.
  if (cronRebuild() === false)
    $cron_error = translate('CronWriteFailed');
}

if ($cron_error !== '') {
  ZM\Error('Scheduled tasks: '.$cron_error);
  $redirect = '?view=cron&error='.urlencode($cron_error);
} else {
  $redirect = '?view=cron';
}
?>
