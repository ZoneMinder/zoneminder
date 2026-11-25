<?php
//
// ZoneMinder snapshot creation shared by the web action and the REST API.
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

namespace ZM;

# Creates a snapshot of the monitors in $monitor_ids, as includes/actions/snapshot.php
# does: force an alarm on each monitor the user may edit, link the event it records,
# then archive those events. Returns the new Snapshot id, or null when nothing was
# created. Callers may do their own Snapshots edit check; the per-monitor checks below
# are the same ones the action applies.
function create_snapshot_from_monitors($monitor_ids) {
  global $user, $dbConn;

  // Load the real classes unless the caller (or a test) already provided them.
  if (!class_exists(__NAMESPACE__.'\Snapshot')) require_once('Snapshot.php');
  if (!class_exists(__NAMESPACE__.'\Monitor')) require_once('Monitor.php');

  // A snapshot forces an alarm on each monitor and archives the events it
  // records, so it needs Snapshots edit, and only monitors the user can edit.
  if (!canEdit('Snapshots')) {
    Warning('Creating a snapshot requires Snapshots edit permission');
    return null;
  }
  if ( ! (is_array($monitor_ids) and count($monitor_ids) > 0 ) ) {
    Error('No monitor ids given in snapshot creation request');
    return null;
  }

  $monitors = array();
  foreach ( $monitor_ids as $monitor_id ) {
    if (!is_scalar($monitor_id) or !preg_match('/^\d+$/', (string)$monitor_id)) {
      Error('Monitor Id value is invalid in snapshot creation request');
      continue;
    }
    $monitor = Monitor::find_one(['Id'=>$monitor_id]);
    if (!$monitor or !visibleMonitor($monitor->Id()) or !$monitor->canEdit()) {
      Error('Monitor not found for id '.$monitor_id);
      continue;
    }
    $monitors[] = $monitor;
  }
  if (!count($monitors)) return null;

  $snapshot = new Snapshot();
  $snapshot->save(array('CreatedBy'=>$user->Id()));
  AuditAction('create', 'snapshot', $snapshot->Id(), '');

  foreach ( $monitors as $monitor ) {
    $monitor_id = $monitor->Id();
    $snapshot_event = new Snapshot_Event();
    $event_id = $monitor->TriggerOn();
    Debug("Have event $event_id for monitor $monitor_id");
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
  if (!count($snapshot->EventIds())) {
    Error('No events were created for snapshot '.$snapshot->Id().', removing it');
    $snapshot->delete();
    return null;
  }
  $dbConn->beginTransaction();
  foreach ( $snapshot->Events() as $event ) {
    $event->lock();
    $event->save(array('Archived'=>1));
  }
  $dbConn->commit();
  return $snapshot->Id();
}
