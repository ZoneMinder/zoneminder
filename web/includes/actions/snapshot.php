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
  if ( ! (isset($_REQUEST['monitor_ids']) and count($_REQUEST['monitor_ids']) > 0 ) ) {
    ZM\Error('No monitor ids given in snapshot creation request');
    return;
  }
  $snapshot = new ZM\Snapshot();
  $snapshot->save(array('CreatedBy'=>$user['Id']));

  foreach ( $_REQUEST['monitor_ids'] as $monitor_id ) {
    if (!validCardinal($monitor_id)) {
      Error("Monitor Id value is invalid $monitor_id");
      continue;
    }
    $monitor = ZM\Monitor::find_one(['Id'=>$monitor_id]);
    if (!$monitor) {
      Error("Monitor not found for id $monitor_id");
      continue;
    }
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
  foreach ( $_REQUEST['monitor_ids'] as $monitor_id ) {
    $monitor = ZM\Monitor::find_one(['Id'=>$monitor_id]);
    if (!$monitor) continue;
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
  $snapshot = new ZM\Snapshot($_REQUEST['id']);
  if ( ($action == 'save') ) {
    if ( canEdit('Events') or $snapshot->CreatedBy() == $user['Id'] ) {

      $changes = $snapshot->changes($_REQUEST['snapshot']);
      if ( count($changes) ) {
        $snapshot->save($changes);
      }
      $redirect = '?view=snapshots';
    }
  } else if ( $action == 'delete' ) {
    if ( canEdit('Events') ) {
      $snapshot->delete();
      $redirect = '?view=snapshots';
    }
  }
}  // end if canEdit(Events)
?>
