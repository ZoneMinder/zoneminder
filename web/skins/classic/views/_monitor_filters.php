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

$servers = Server::find_all( null, array('order'=>'lower(Name)'));
$ServersById = array();
foreach ( $servers as $S ) {
  $ServersById[$S->Id()] = $S;
}
session_start();
foreach ( array('Group', 'ServerId','StorageId','Status','MonitorId') as $var ) {
  if ( isset( $_REQUEST[$var] ) ) {
    if ( $_REQUEST[$var] != '' ) {
      $_SESSION[$var] = $_REQUEST[$var];
    } else {
      unset( $_SESSION[$var] );
    }
  } else if ( isset( $_REQUEST['filtering'] ) ) {
    unset( $_SESSION[$var] );
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
<input type="hidden" name="filtering" value="" />
<?php
$groupSql = '';
 if ( Group::find_all() ) { ?>
  <span id="groupControl"><label><?php echo translate('Group') ?>:</label>
<?php
# This will end up with the group_id of the deepest selection
$group_id = isset($_SESSION['Group']) ?  $_SESSION['Group'] : null;
echo Group::get_group_dropdown();
$groupSql = Group::get_group_sql( $group_id );
?>
  </span>
<?php } ?>
  <span id="monitorControl"><label><?php echo translate('Monitor') ?>:</label>
<?php

$selected_monitor_ids = isset($_SESSION['MonitorId']) ? $_SESSION['MonitorId'] : array();
if ( ! is_array( $selected_monitor_ids ) ) {
  Warning("Turning selected_monitor_ids into an array $selected_monitor_ids");
  $selected_monitor_ids = array( $selected_monitor_ids );
}

  $conditions = array();
  $values = array();

  if ( $groupSql )
    $conditions[] = $groupSql;
  foreach ( array('ServerId','StorageId','Status') as $filter ) {
    if ( isset($_SESSION[$filter]) ) {
      if ( is_array($_SESSION[$filter]) ) {
        $conditions[] = $filter . ' IN ('.implode(',', array_map(function(){return '?';}, $_SESSION[$filter] ) ). ')';
        $values += $_SESSION[$filter];
    } else {
        $conditions[] = $filter . '=?';
      $values[] = $_SESSION[$filter];
      }
    }
  } # end foreach filter
  if ( ! empty( $user['MonitorIds'] ) ) {
    $ids = explode(',', $user['MonitorIds'] );
    $conditions[] = 'M.Id IN ('.implode(',',array_map( function(){return '?';}, $ids) ).')';
    $values += $ids;
  }

  $sql = 'SELECT *,S.Status AS Status, S.CaptureFPS AS CaptureFPS FROM Monitors AS M LEFT JOIN Monitor_Status AS S ON S.Id=M.Id ' . ( count($conditions) ? ' WHERE ' . implode(' AND ', $conditions ) : '' ).' ORDER BY Sequence ASC';
  $monitors = dbFetchAll( $sql, null, $values );
  $displayMonitors = array();
  $monitors_dropdown = array();

  # Check to see if the selected monitor_id is in the results.
  if ( count($selected_monitor_ids) ) {
    $found_selected_monitor = false;

    for ( $i = 0; $i < count($monitors); $i++ ) {
      if ( !visibleMonitor( $monitors[$i]['Id'] ) ) {
        continue;
      }
      if ( in_array( $monitors[$i]['Id'], $selected_monitor_ids ) ) {
        $found_selected_monitor = true;
      }
    } // end foreach monitor
    if ( ! $found_selected_monitor ) {
      $selected_monitor_ids = array();
    }
  } // end if a monitor was specified

  for ( $i = 0; $i < count($monitors); $i++ ) {
    if ( !visibleMonitor( $monitors[$i]['Id'] ) ) {
      Warning("Monitor " . $monitors[$i]['Id'] . ' is not visible' );
      continue;
    }
    $monitors_dropdown[$monitors[$i]['Id']] = $monitors[$i]['Name'];

    if ( count($selected_monitor_ids) and ! in_array( $monitors[$i]['Id'], $selected_monitor_ids ) ) {
      continue;
    }
    if ( isset($_SESSION['StatusFilter']) ) {
      if ( $monitors[$i]['Status'] != $_SESSION['StatusFilter'] ) {
        continue;
      }
    }
    $displayMonitors[] = $monitors[$i];
  }
  echo htmlSelect( 'MonitorId[]', $monitors_dropdown, $selected_monitor_ids,
    array(
      'onchange'=>'this.form.submit();',
      'class'=>'chosen',
      'multiple'=>'multiple',
      'data-placeholder'=>'All',
    ) );
?>
</span>
<?php
if ( count($ServersById) > 1 ) {
?>
<span class="ServerFilter"><label><?php echo translate('Server')?>:</label>
<?php
  echo htmlSelect( 'ServerId[]', $ServersById,
    (isset($_SESSION['ServerId'])?$_SESSION['ServerId']:''),
    array(
      'onchange'=>'this.form.submit();',
      'class'=>'chosen',
      'multiple'=>'multiple',
      'data-placeholder'=>'All',
    )
  );
?>
</span>
<?php 
}
if ( count($StorageById) > 1 ) { ?>
  <span class="StorageFilter"><label><?php echo translate('Storage')?>:</label>
<?php
  echo htmlSelect( 'StorageId[]',$StorageById,
    (isset($_SESSION['StorageId'])?$_SESSION['StorageId']:''),
    array(
      'onchange'=>'this.form.submit();',
      'class'=>'chosen',
      'multiple'=>'multiple',
      'data-placeholder'=>'All',
    ) );
?>
  </span>
<?php
}
?>
  <span class="StatusFilter"><label><?php echo translate('Status')?>:</label>
<?php
$status_options = array(
    'Unknown' => translate('Unknown'),
    'NotRunning' => translate('NotRunning'),
    'Running' => translate('Running'),
    );
echo htmlSelect( 'Status[]', $status_options,
  ( isset($_SESSION['Status']) ? $_SESSION['Status'] : '' ),
  array(
    'onchange'=>'this.form.submit();',
    'class'=>'chosen',
    'multiple'=>'multiple',
    'data-placeholder'=>'All'
  ) );
?>
  </span>
</div>
