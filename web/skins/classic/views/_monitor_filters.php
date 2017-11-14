<?php
//
// ZoneMinder web console file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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

$servers = Server::find_all();
$ServersById = array();
foreach ( $servers as $S ) {
  $ServersById[$S->Id()] = $S;
}
session_start();
foreach ( array('ServerFilter','StorageFilter','StatusFilter','MonitorId') as $var ) {
  if ( isset( $_REQUEST[$var] ) ) {
    if ( $_REQUEST[$var] != '' ) {
      $_SESSION[$var] = $_REQUEST[$var];
    } else {
      unset( $_SESSION[$var] );
    }
  } else if ( isset( $_COOKIE[$var] ) ) {
    if ( $_COOKIE[$var] != '' ) {
      $_SESSION[$var] = $_COOKIE[$var];
    } else {
      unset($_SESSION[$var]);
    }
  }
}
session_write_close();

$storage_areas = Storage::find_all();
$StorageById = array();
foreach ( $storage_areas as $S ) {
  $StorageById[$S->Id()] = $S;
}

?>
<div class="controlHeader">
  <span id="groupControl"><label><?php echo translate('Group') ?>:</label>
<?php
# This will end up with the group_id of the deepest selection
$group_id = Group::get_group_dropdowns();
$groupSql = Group::get_group_sql( $group_id );
?>
  </span>
  <span id="monitorControl"><label><?php echo translate('Monitor') ?>:</label>
<?php

  $monitor_id = isset($_SESSION['MonitorId']) ? $_SESSION['MonitorId'] : 0;

  # Used to determine if the Cycle button should be made available

  $conditions = array();
  $values = array();

  if ( $groupSql )
    $conditions[] = $groupSql;
  if ( isset($_SESSION['ServerFilter']) ) {
    $conditions[] = 'ServerId=?';
    $values[] = $_SESSION['ServerFilter'];
  }
  if ( isset($_SESSION['StorageFilter']) ) {
    $conditions[] = 'StorageId=?';
    $values[] = $_SESSION['StorageFilter'];
  }
  if ( ! empty( $user['MonitorIds'] ) ) {
    $ids = explode(',', $user['MonitorIds'] );
    $conditions[] = 'Id IN ('.implode(',',array_map( function(){return '?';}, $ids) ).')';
    $values += $ids;
  }

  $sql = 'SELECT * FROM Monitors' . ( count($conditions) ? ' WHERE ' . implode(' AND ', $conditions ) : '' ).' ORDER BY Sequence ASC';
  $monitors = dbFetchAll( $sql, null, $values );
  $displayMonitors = array();
  $monitors_dropdown = array(''=>'All');

  if ( $monitor_id ) {
    $found_selected_monitor = false;

    for ( $i = 0; $i < count($monitors); $i++ ) {
      if ( !visibleMonitor( $monitors[$i]['Id'] ) ) {
        continue;
      }
      $monitors_dropdown[$monitors[$i]['Id']] = $monitors[$i]['Name'];
      if ( $monitors[$i]['Id'] == $monitor_id ) {
        $found_selected_monitor = true;
      }
    }
    if ( ! $found_selected_monitor ) {
      $monitor_id = '';
    }
  }
  for ( $i = 0; $i < count($monitors); $i++ ) {
    if ( !visibleMonitor( $monitors[$i]['Id'] ) ) {
      continue;
    }
    $monitors_dropdown[$monitors[$i]['Id']] = $monitors[$i]['Name'];

    if ( $monitor_id and ( $monitors[$i]['Id'] != $monitor_id ) ) {
      continue;
    }
    $displayMonitors[] = $monitors[$i];
  }
  echo htmlSelect( 'MonitorId', $monitors_dropdown, $monitor_id, array('onchange'=>'changeMonitor(this);') );
?>
</span>
<?php
if ( count($ServersById) > 1 ) {
?>
<span class="ServerFilter"><label><?php echo translate('Server')?>:</label>
<?php
echo htmlSelect( 'ServerFilter', array(''=>'All')+$ServersById, (isset($_SESSION['ServerFilter'])?$_SESSION['ServerFilter']:''), array('onchange'=>'changeFilter(this);') );
?>
</span>
<?php 
}
if ( count($StorageById) > 1 ) { ?>
  <span class="StorageFilter"><label><?php echo translate('Storage')?>:</label>
<?php
echo htmlSelect( 'StorageFilter', array(''=>'All')+$StorageById, (isset($_SESSION['StorageFilter'])?$_SESSION['StorageFilter']:''), array('onchange'=>'changeFilter(this);') );
?>
  </span>
<?php
}
?>
  <span class="StatusFilter"><label><?php echo translate('Status')?>:</label>
<?php
$status_options = array(
    ''=>'All',
    'Unknown' => translate('Unknown'),
    'NotRunning' => translate('NotRunning'),
    'Running' => translate('Running'),
    );
echo htmlSelect( 'StatusFilter', $status_options, ( isset($_SESSION['StatusFilter']) ? $_SESSION['StatusFilter'] : '' ), array('onchange'=>'changeFilter(this);') );
?>
  </span>
</div>
