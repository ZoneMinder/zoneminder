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

// System edit actions
if ( !canEdit('System') ) {
  ZM\Warning('Need System Permission to edit states');
  return;
}
if ( $action == 'state' ) {
  if ( !empty($_REQUEST['runState']) ) {
    //if ( $cookies ) session_write_close();
    packageControl($_REQUEST['runState']);
    $refreshParent = true;
  }
} else if ( $action == 'save' ) {
  if ( !empty($_REQUEST['runState']) || !empty($_REQUEST['newState']) ) {
    $sql = 'SELECT `Id`,`Function`,`Enabled` FROM Monitors ORDER BY Id';
    $definitions = array();
    foreach ( dbFetchAll($sql) as $monitor ) {
      $definitions[] = $monitor['Id'].':'.$monitor['Function'].':'.$monitor['Enabled'];
    }
    $definition = join(',', $definitions);
    if ( $_REQUEST['newState'] )
      $_REQUEST['runState'] = $_REQUEST['newState'];
    dbQuery('REPLACE INTO `States` SET `Name`=?, `Definition`=?', array($_REQUEST['runState'],$definition));
  }
} else if ( $action == 'delete' ) {
  if ( isset($_REQUEST['runState']) )
    dbQuery('DELETE FROM `States` WHERE `Name`=?', array($_REQUEST['runState']));
}
$view = 'console';
?>
