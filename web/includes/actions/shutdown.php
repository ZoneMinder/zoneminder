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

if ( !canEdit('System') ) {
  ZM\Warning('Need System permissions to shutdown server');
  return;
}
if ( $action ) {
  $when = isset($_POST['when']) and $_POST['when'] == 'now' ? 'now' : '+1';
  if ( $action == 'shutdown' ) {
    $output = array();
    $rc = 0;
    exec('sudo -n '.ZM_PATH_SHUTDOWN." -P $when 2>&1", $output, $rc);
    #exec('sudo -n /bin/systemctl poweroff -i 2>&1', $output, $rc);
    ZM\Logger::Debug("Shutdown output $rc " . implode("\n",$output));
    #ZM\Logger::Debug("Shutdown output " . shell_exec('/bin/systemctl poweroff -i 2>&1'));
  } else if ( $action == 'restart' ) {
    $output = array();
    exec('sudo -n '.ZM_PATH_SHUTDOWN." -r $when 2>&1", $output);
    #exec('sudo -n /bin/systemctl reboot -i 2>&1', $output);
    ZM\Logger::Debug("Shutdown output " . implode("\n",$output));
  } else if ( $action == 'cancel' ) {
    $output = array();
    exec('sudo '.ZM_PATH_SHUTDOWN.' -c 2>&1', $output);
  }
} # end if action
?>
