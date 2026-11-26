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
require_once(__DIR__.'/../SnapshotCreate.php');

if ( $action == 'create' ) {
  $snapshot_id = ZM\create_snapshot_from_monitors(
    isset($_REQUEST['monitor_ids']) && is_array($_REQUEST['monitor_ids']) ? $_REQUEST['monitor_ids'] : array()
  );
  if (!$snapshot_id) return;
  $redirect = '?view=snapshot&id='.$snapshot_id;
  return;
}

// Event scope actions, view permissions only required
if ( isset($_REQUEST['id']) ) {
  $snapshot = new ZM\Snapshot(validCardinal($_REQUEST['id']));
  if ( !$snapshot->Id() ) {
    // Saving an object that failed to load would insert a new row
    ZM\Warning('Snapshot '.$_REQUEST['id'].' not found');
    $redirect = '?view=snapshots';
    return;
  }
  if ( ($action == 'save') ) {
    if ( canEdit('Events') or $snapshot->CreatedBy() == $user->Id() ) {

      $changes = $snapshot->changes(isset($_REQUEST['snapshot']) && is_array($_REQUEST['snapshot']) ? $_REQUEST['snapshot'] : array());
      if ( count($changes) ) {
        $snapshot->save($changes);
        ZM\AuditAction('update', 'snapshot', $snapshot->Id(), 'Changed: '.implode(', ', array_keys($changes)));
      }
      $redirect = '?view=snapshots';
    } else {
      global $error_message;
      $error_message .= 'You do not have permission to edit this snapshot.<br/>';
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
