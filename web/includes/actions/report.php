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
  $report = new ZM\Report($_REQUEST['id']);
} else {
  $report = new ZM\Report();
}
global $redirect;
global $error_message;

if ($report->Id() and !$report->canEdit()) {
  ZM\Warning('You do not have permission to edit report '.$report->Id());
  $redirect = '?view=reports';
  return;
}

if ($action == 'save') {
  $changes = $report->changes($_REQUEST['Report']);
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
  $report->delete();
  $redirect = '?view=reports';
} else {
  ZM\Error("Unknown action $action in saving Report");
}
?>
