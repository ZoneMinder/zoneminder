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
if (!canEdit('System')) {
  ZM\Warning('Need System Permission to edit states');
  return;
}
if ($action == 'state') {
  if (!empty($_REQUEST['runState']) and is_string($_REQUEST['runState'])) {
    packageControl($_REQUEST['runState']);
    ZM\AuditAction('apply', 'state', 0, 'State: '.$_REQUEST['runState']);
    $refreshParent = true;
  }
} else if ($action == 'save') {
  $stateName = !empty($_REQUEST['newState']) ? $_REQUEST['newState'] : ($_REQUEST['runState'] ?? '');
  if (!is_string($stateName)) $stateName = '';
  // zmpkg.pl treats these names as commands, so a state with one of them could never be applied
  if (preg_match('/^(?:start|stop|restart|status|logrot|version)$/', $stateName)) {
    global $error_message;
    ZM\Error('A run state cannot be named '.$stateName);
    $error_message .= 'A run state cannot be named '.validHtmlStr($stateName).'<br/>';
  } else if (!empty($stateName)) {
    $sql = 'SELECT `Id`,`Capturing`,`Analysing`,`Recording` FROM `Monitors` WHERE `Deleted`=false ORDER BY `Id`';
    $definitions = array();
    foreach (dbFetchAll($sql) as $monitor ) {
      $definitions[] = $monitor['Id'].':'.$monitor['Capturing'].':'.$monitor['Analysing'].':'.$monitor['Recording'];
    }
    $definition = join(',', $definitions);
    if (!empty($_REQUEST['newState']))
      $_REQUEST['runState'] = $_REQUEST['newState'];
    dbQuery('INSERT INTO `States` (`Name`, `Definition`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `Definition`=VALUES(`Definition`)', array($_REQUEST['runState'], $definition));
    ZM\AuditAction('save', 'state', 0, 'Name: '.$_REQUEST['runState']);
  }
} else if ($action == 'delete') {
  if (isset($_REQUEST['runState']) and is_string($_REQUEST['runState'])) {
    dbQuery('DELETE FROM `States` WHERE `Name`=?', array($_REQUEST['runState']));
    // Deleting the active state would leave none active, so events would be recorded with no StateId. Fall back to default like zmpkg.pl does.
    dbQuery('UPDATE `States` SET `IsActive`=1 WHERE `Name`=\'default\' AND NOT EXISTS (SELECT 1 FROM (SELECT `Id` FROM `States` WHERE `IsActive`=1) AS `Active`)');
    ZM\AuditAction('delete', 'state', 0, 'Name: '.$_REQUEST['runState']);
  }
}
$redirect = '?view='.getHomeView();
?>
