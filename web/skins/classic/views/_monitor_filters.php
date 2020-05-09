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

$servers = ZM\Server::find(null, array('order'=>'lower(Name)'));
$ServersById = array();
foreach ( $servers as $S ) {
  $ServersById[$S->Id()] = $S;
}
session_start();
foreach ( array('GroupId','Function','ServerId','StorageId','Status','MonitorId','MonitorName','Source') as $var ) {
  if ( isset($_REQUEST[$var]) ) {
    if ( $_REQUEST[$var] != '' ) {
      $_SESSION[$var] = $_REQUEST[$var];
    } else {
      unset($_SESSION[$var]);
    }
  } else if ( isset($_REQUEST['filtering']) ) {
    unset($_SESSION[$var]);
  }
}
session_write_close();

$storage_areas = ZM\Storage::find();
$StorageById = array();
foreach ( $storage_areas as $S ) {
  $StorageById[$S->Id()] = $S;
}

$html =
'
<div class="controlHeader">
  <!-- Used to submit the form with the enter key -->
  <input type="submit" class="hide"/>
  <input type="hidden" name="filtering" value=""/>
';

$GroupsById = array();
foreach ( ZM\Group::find() as $G ) {
  $GroupsById[$G->Id()] = $G;
}

$groupSql = '';
if ( count($GroupsById) ) {
  $html .= '<span id="groupControl"><label>'. translate('Group') .'</label>';
  # This will end up with the group_id of the deepest selection
  $group_id = isset($_SESSION['GroupId']) ? $_SESSION['GroupId'] : null;
  $html .= ZM\Group::get_group_dropdown();
  $groupSql = ZM\Group::get_group_sql($group_id);
  $html .= '</span>
';
}

$selected_monitor_ids = isset($_SESSION['MonitorId']) ? $_SESSION['MonitorId'] : array();
if ( !is_array($selected_monitor_ids) ) {
  $selected_monitor_ids = array($selected_monitor_ids);
}

$conditions = array();
$values = array();

if ( $groupSql )
  $conditions[] = $groupSql;
foreach ( array('ServerId','StorageId','Status','Function') as $filter ) {
  if ( isset($_SESSION[$filter]) ) {
    if ( is_array($_SESSION[$filter]) ) {
      $conditions[] = $filter . ' IN ('.implode(',', array_map(function(){return '?';}, $_SESSION[$filter])). ')';
      $values = array_merge($values, $_SESSION[$filter]);
    } else {
      $conditions[] = $filter . '=?';
      $values[] = $_SESSION[$filter];
    }
  }
} # end foreach filter

if ( !empty($user['MonitorIds']) ) {
  $ids = explode(',', $user['MonitorIds']);
  $conditions[] = 'M.Id IN ('.implode(',',array_map(function(){return '?';}, $ids)).')';
  $values = array_merge($values, $ids);
}

$html .= '<span class="MonitorNameFilter"><label>'.translate('Name').'</label>';
$html .= '<input type="text" name="MonitorName" value="'.(isset($_SESSION['MonitorName'])?validHtmlStr($_SESSION['MonitorName']):'').'" placeholder="text or regular expression"/>';
$html .= '</span>
';

$Functions = array();
foreach ( getEnumValues('Monitors', 'Function') as $optFunction ) {
  $Functions[$optFunction] = translate('Fn'.$optFunction);
}

$html .= '<span class="FunctionFilter"><label>'.translate('Function').'</label>';
$html .= htmlSelect('Function[]', $Functions,
  (isset($_SESSION['Function'])?$_SESSION['Function']:''),
    array(
      'data-on-change'=>'submitThisForm',
      'class'=>'chosen',
      'multiple'=>'multiple',
      'data-placeholder'=>'All',
    )
 );
$html .= '</span>
';

if ( count($ServersById) > 1 ) {
  $html .= '<span class="ServerFilter"><label>'. translate('Server').'</label>';
  $html .= htmlSelect('ServerId[]', $ServersById,
    (isset($_SESSION['ServerId'])?$_SESSION['ServerId']:''),
    array(
      'data-on-change'=>'submitThisForm',
      'class'=>'chosen',
      'multiple'=>'multiple',
      'data-placeholder'=>'All',
    )
  );
  $html .= '</span>
';
} # end if have Servers

if ( count($StorageById) > 1 ) {
  $html .= '<span class="StorageFilter"><label>'.translate('Storage').'</label>';
  $html .= htmlSelect('StorageId[]', $StorageById,
    (isset($_SESSION['StorageId'])?$_SESSION['StorageId']:''),
    array(
      'data-on-change'=>'submitThisForm',
      'class'=>'chosen',
      'multiple'=>'multiple',
      'data-placeholder'=>'All',
    ) );
  $html .= '</span>
';
} # end if have Storage Areas

$html .= '<span class="StatusFilter"><label>'.translate('Status').'</label>';
$status_options = array(
    'Unknown' => translate('StatusUnknown'),
    'NotRunning' => translate('StatusNotRunning'),
    'Running' => translate('StatusRunning'),
    'Connected' => translate('StatusConnected'),
    );
$html .= htmlSelect( 'Status[]', $status_options,
  ( isset($_SESSION['Status']) ? $_SESSION['Status'] : '' ),
  array(
    'data-on-change'=>'submitThisForm',
    'class'=>'chosen',
    'multiple'=>'multiple',
    'data-placeholder'=>'All'
  ) );
$html .= '</span>
';

  $html .= '<span class="SourceFilter"><label>'.translate('Source').'</label>';
  $html .= '<input type="text" name="Source" value="'.(isset($_SESSION['Source'])?validHtmlStr($_SESSION['Source']):'').'" placeholder="text or regular expression"/>';
  $html .= '</span>
';

  $sql = 'SELECT *,S.Status AS Status, S.CaptureFPS AS CaptureFPS, S.AnalysisFPS AS AnalysisFPS, S.CaptureBandwidth AS CaptureBandwidth
  FROM Monitors AS M LEFT JOIN Monitor_Status AS S ON MonitorId=Id ' .
  ( count($conditions) ? ' WHERE ' . implode(' AND ', $conditions) : '' ).' ORDER BY Sequence ASC';
  $monitors = dbFetchAll($sql, null, $values);
  $displayMonitors = array();
  $monitors_dropdown = array();

  # Check to see if the selected monitor_id is in the results.
  if ( count($selected_monitor_ids) ) {
    $found_selected_monitor = false;

    for ( $i = 0; $i < count($monitors); $i++ ) {
      if ( !visibleMonitor($monitors[$i]['Id']) ) {
        continue;
      }
      if ( in_array($monitors[$i]['Id'], $selected_monitor_ids) ) {
        $found_selected_monitor = true;
      }
    } // end foreach monitor
    if ( !$found_selected_monitor ) {
      $selected_monitor_ids = array();
    }
  } // end if a monitor was specified

  for ( $i = 0; $i < count($monitors); $i++ ) {
    if ( !visibleMonitor($monitors[$i]['Id']) ) {
      ZM\Logger::Warning('Monitor '.$monitors[$i]['Id'].' is not visible');
      continue;
    }

    if ( isset($_SESSION['MonitorName']) ) {
      $Monitor = new ZM\Monitor($monitors[$i]);
      ini_set('track_errors', 'on');
      $php_errormsg = '';
      $regexp = $_SESSION['MonitorName'];

      @preg_match($regexp, '');
      if ( $php_errormsg ) {
        $regexp = '/'.preg_quote($regexp,'/').'/i';
      }

      if ( !preg_match($regexp, $Monitor->Name()) ) {
        continue;
      }
    }

    if ( isset($_SESSION['Source']) ) {
      $Monitor = new ZM\Monitor($monitors[$i]);
      ini_set('track_errors', 'on');
      $php_errormsg = '';
      $regexp = $_SESSION['Source'];

      @preg_match($regexp, '');
      if ( $php_errormsg ) {
        $regexp = '/'.preg_quote($regexp,'/').'/i';
      }
      if ( !preg_match($regexp, $Monitor->Source()) ) {
        if ( !preg_match($regexp, $Monitor->Path()) ) {
          continue;
        }
      }
    }

    $monitors_dropdown[$monitors[$i]['Id']] = $monitors[$i]['Id'].' '.$monitors[$i]['Name'];

    if ( count($selected_monitor_ids) and ! in_array($monitors[$i]['Id'], $selected_monitor_ids) ) {
      continue;
    }
    $displayMonitors[] = $monitors[$i];
  } # end foreach monitor

  $html .= '<span class="MonitorFilter"><label>'.translate('Monitor').'</label>';
  $html .= htmlSelect('MonitorId[]', $monitors_dropdown, $selected_monitor_ids,
    array(
      'data-on-change'=>'submitThisForm',
      'class'=>'chosen',
      'multiple'=>'multiple',
      'data-placeholder'=>'All',
    ) );
# Repurpose this variable to be the list of MonitorIds as a result of all the filtering
$selected_monitor_ids = array_map(function($monitor_row){return $monitor_row['Id'];}, $displayMonitors);
$html .= '</span>
';
  echo $html;
?>
</div>
