<?php
//
// ZoneMinder web action file
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

require_once('includes/Report.php');
if (!empty($_REQUEST['id'])) {
  $report = new ZM\Report(validCardinal($_REQUEST['id']));
} else {
  $report = new ZM\Report();
}
global $redirect;
global $error_message;

if (!empty($_REQUEST['id']) and !$report->Id()) {
  // Saving a report that failed to load would insert a new row
  ZM\Warning('Report '.$_REQUEST['id'].' not found');
  $redirect = '?view=reports';
  return;
}

if (!$report->Id() and !canEdit('Events')) {
  ZM\Warning('You do not have permission to create a report');
  $error_message .= 'You do not have permission to create a report.<br/>';
  return;
}

if ($report->Id() and !$report->canEdit()) {
  ZM\Warning('You do not have permission to edit report '.$report->Id());
  $error_message .= 'You do not have permission to edit this report.<br/>';
  return;
}

if ($action == 'save') {
  $posted = isset($_REQUEST['Report']) && is_array($_REQUEST['Report']) ? $_REQUEST['Report'] : array();
  if (isset($posted['Name'])) {
    $posted['Name'] = is_string($posted['Name']) ? trim($posted['Name']) : '';
    if ($posted['Name'] === '') {
      $error_message .= 'Report Name is required<br/>';
      return;
    }
  }
  $changes = $report->changes($posted);
  if (!$report->Id()) {
    $changes['CreatedBy'] = $user->Id();
  }

  if (count($changes)) {
    if (!$report->save($changes)) {
      $error_message .= "Error saving report: " . $report->get_last_error().'</br>';
    } else {
      $redirect = '?view=report&id='.$report->Id();
    }
  }
} else if ($action == 'delete') {
  if (!$report->delete()) {
    $error_message .= 'Error deleting report: '.htmlspecialchars(dbLastError()).'<br/>';
  } else {
    $redirect = '?view=reports';
  }
} else {
  ZM\Error("Unknown action $action in saving Report");
}
?>
