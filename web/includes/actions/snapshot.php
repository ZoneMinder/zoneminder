<?php
//
// ZoneMinder web action
// Copyright (C) 2019 ZoneMinder LLC
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

require_once('includes/Snapshot.php');
require_once('includes/Monitor.php');

if ( $action == 'create' ) {
  // A snapshot forces an alarm on each monitor and archives the events it
  // records, so it needs Snapshots edit, and only monitors the user can view.
  if (!canEdit('Snapshots')) {
    ZM\Warning('Creating a snapshot requires Snapshots edit permission');
    return;
  }
  if ( ! (isset($_REQUEST['monitor_ids']) and is_array($_REQUEST['monitor_ids']) and count($_REQUEST['monitor_ids']) > 0 ) ) {
    ZM\Error('No monitor ids given in snapshot creation request');
    return;
  }
  $monitors = array();
  foreach ( $_REQUEST['monitor_ids'] as $monitor_id ) {
    if (!is_scalar($monitor_id) or !preg_match('/^\d+$/', (string)$monitor_id)) {
      ZM\Error('Monitor Id value is invalid in snapshot creation request');
      continue;
    }
    $monitor = ZM\Monitor::find_one(['Id'=>$monitor_id]);
    if (!$monitor or !visibleMonitor($monitor->Id())) {
      ZM\Error('Monitor not found for id '.$monitor_id);
      continue;
    }
    $monitors[] = $monitor;
  }
  if (!count($monitors)) return;

  $snapshot = new ZM\Snapshot();
  $snapshot->save(array('CreatedBy'=>$user->Id()));
  ZM\AuditAction('create', 'snapshot', $snapshot->Id(), '');

  foreach ( $monitors as $monitor ) {
    $monitor_id = $monitor->Id();
    $snapshot_event = new ZM\Snapshot_Event();
    $event_id = $monitor->TriggerOn();
    ZM\Debug("Have event $event_id for monitor $monitor_id");
    if ( $event_id ) {
      $snapshot_event->save(array(
        'SnapshotId'=>$snapshot->Id(),
        'EventId'=>$event_id
      ));
    }
  }  # end foreach monitor
  foreach ( $monitors as $monitor ) {
    $monitor->TriggerOff();
  }
  $dbConn->beginTransaction();
  foreach ( $snapshot->Events() as $event ) {
    $event->lock();
    $event->save(array('Archived'=>1));
  }
  $dbConn->commit();
  $redirect = '?view=snapshot&id='.$snapshot->Id();
  return;
}

// Event scope actions, view permissions only required
if ( isset($_REQUEST['id']) ) {
  $snapshot = new ZM\Snapshot(validCardinal($_REQUEST['id']));
  if ( ($action == 'save') ) {
    if ( canEdit('Events') or $snapshot->CreatedBy() == $user->Id() ) {

      $changes = $snapshot->changes($_REQUEST['snapshot']);
      if ( count($changes) ) {
        $snapshot->save($changes);
        ZM\AuditAction('update', 'snapshot', $snapshot->Id(), 'Changed: '.implode(', ', array_keys($changes)));
      }
      $redirect = '?view=snapshots';
    }
  } else if ( $action == 'delete' ) {
    if ( canEdit('Events') ) {
      $snapshot->delete();
      ZM\AuditAction('delete', 'snapshot', $_REQUEST['id'], '');
      $redirect = '?view=snapshots';
    }
  }
}  // end if canEdit(Events)
?>
