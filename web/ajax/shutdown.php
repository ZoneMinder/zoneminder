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

$message = '';
if ( !canEdit('System') ) {
  $message = 'Need System permissions to shutdown server';
} else if ( !isset($_REQUEST['command']) ) {
  $message = 'A command is required. Cannot continue';
}

if ( $message ) {
  ZM\Warning($message);
  ajaxError($message);
  return;
}

$data = array();
$when = isset($_REQUEST['when']) and $_REQUEST['when'] == 'now' ? 'now' : '+1';
$command = $_REQUEST['command'];

if ( $command == 'shutdown' ) {
  exec('sudo -n '.ZM_PATH_SHUTDOWN." -P $when 2>&1", $data['output'], $data['rc']);
  #exec('sudo -n /bin/systemctl poweroff -i 2>&1', $data['output'], $data['rc']);
  ZM\Debug('Shutdown output ' .$data['rc'].' '.implode("\n",$data['output']));
  #ZM\Debug("Shutdown output " . shell_exec('/bin/systemctl poweroff -i 2>&1'));
} else if ( $command == 'restart' ) {
  $data['output'] = array();
  exec('sudo -n '.ZM_PATH_SHUTDOWN." -r $when 2>&1", $data['output'], $data['rc']);
  #exec('sudo -n /bin/systemctl reboot -i 2>&1', $data['output'], $data['rc']);
  ZM\Debug("Shutdown output " . implode("\n",$data['output']));
} else if ( $command == 'cancel' ) {
  $data['output'] = array();
  exec('sudo '.ZM_PATH_SHUTDOWN.' -c 2>&1', $data['output'], $data['rc']);
} else {
  ajaxError('Unknwn command:'.$command);
  return;
}

ajaxResponse($data);
return;
?>
