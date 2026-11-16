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


// Monitor control actions, require a monitor id and control view permissions for that monitor
if (empty($_REQUEST['mid'])) {
  ZM\Warning('Settings requires a monitor id');
  return;
}
// Changing the settings needs Control edit, as the settings modal shows
// (ajax/modals/settings.php enables its sliders only with canEdit('Control')).
// canEdit('Control', $mid) would also demand Monitors edit, so check the monitor
// with canView and the area with canEdit.
if (!canView('Control', $_REQUEST['mid']) or !canEdit('Control')) {
  ZM\Warning('Settings requires the Control edit permission');
  return;
}

require_once('includes/Monitor.php');
$mid = validInt($_REQUEST['mid']);
if ($action == 'settings') {
  $args = ' -m ' . escapeshellarg($mid);
  $args .= ' -B' . escapeshellarg($_REQUEST['newBrightness']);
  $args .= ' -C' . escapeshellarg($_REQUEST['newContrast']);
  $args .= ' -H' . escapeshellarg($_REQUEST['newHue']);
  $args .= ' -O' . escapeshellarg($_REQUEST['newColour']);

  $zmuCommand = getZmuCommand($args);

  $zmuOutput = exec($zmuCommand);
  // zmu prints the four values it set; it prints nothing when it refuses (it
  // checks the user's own permissions) or fails. Store only what it reported.
  if (preg_match('/^(-?\d+) (-?\d+) (-?\d+) (-?\d+)$/', trim((string)$zmuOutput), $values)) {
    list(, $brightness, $contrast, $hue, $colour) = $values;
    dbQuery(
      'UPDATE Monitors SET Brightness = ?, Contrast = ?, Hue = ?, Colour = ? WHERE Id = ?',
      array($brightness, $contrast, $hue, $colour, $mid));
  } else {
    ZM\Warning('zmu did not report the new settings for monitor '.$mid.', not saving them');
  }
  global $redirect;
  $redirect = '?view=watch&mid='.$mid;
}
?>
