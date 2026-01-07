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

function addFilterSelect($name, $options) {
  global $view;
  // Use monitorFilterOnChange on console view for AJAX refresh, submitThisForm elsewhere
  $onChangeFunction = ($view == 'console') ? 'monitorFilterOnChange' : 'submitThisForm';
  
  $html = '<span class="term '.$name.'Filter"><label>'.translate($name).'</label>';
  $html .= '<span class="term-value-wrapper">';
  $html .= htmlSelect($name.'[]', $options,
    (isset($_SESSION[$name])?$_SESSION[$name]:''),
      array(
        'data-on-change'=>$onChangeFunction,
        'class'=>'chosen',
        'multiple'=>'multiple',
        'data-placeholder'=>'All',
      )
    );
  $html .= addButtonResetForFilterSelect($name.'[]');
  $html .= '</span>';
  $html .= '</span>'.PHP_EOL;
  return $html;
}

function addButtonResetForFilterSelect($nameSelect) {
  if (isset($_COOKIE['zmUseOldMenuView']) and $_COOKIE["zmUseOldMenuView"] === 'true') {
    $html = '';
  } else {
    $html = PHP_EOL . '
      <span class="btn-term-remove-all">
        <button type="button" name="clearAllBtn" data-on-click-this="resetSelectElement" data-select-target="'.$nameSelect.'">
          <i class="material-icons">clear</i>
          <span class="text"></span>
        </button>
      </span>' . PHP_EOL;
  }
  return $html;
}

function buildMonitorsFilters() {
  global $user, $Servers, $view;
  require_once('includes/Monitor.php');
  
  // Use monitorFilterOnChange on console view for AJAX refresh, submitThisForm elsewhere
  $onChangeFunction = ($view == 'console') ? 'monitorFilterOnChange' : 'submitThisForm';

  zm_session_start();
  foreach (array('GroupId','Capturing','Analysing','Recording','ServerId','StorageId','Status','MonitorId','MonitorName','Source') as $var) {
    if (isset($_REQUEST[$var])) {
      if ($_REQUEST[$var] != '') {
        $_SESSION[$var] = $_REQUEST[$var];
      } else {
        unset($_SESSION[$var]);
      }
    } else if (isset($_REQUEST['filtering'])) {
      unset($_SESSION[$var]);
    } else if (!isset($_SESSION[$var])) {
      // Restore from cookie if session doesn't have a value
      $cookieName = 'zmFilter_'.$var;
      if (isset($_COOKIE[$cookieName])) {
        $cookieValue = $_COOKIE[$cookieName];
        if ($cookieValue && $cookieValue !== '') {
          // Try to decode JSON for array values
          $decoded = json_decode($cookieValue, true);
          $_SESSION[$var] = ($decoded !== null) ? $decoded : $cookieValue;
        }
      }
    }
  }
  session_write_close();

  $storage_areas = ZM\Storage::find();
  $StorageById = array();
  foreach ($storage_areas as $S) {
    $StorageById[$S->Id()] = $S;
  }

  $ServersById = array();
  foreach ($Servers as $s) {
    $ServersById[$s->Id()] = $s;
  }

  $html =
'
<div class="controlHeader">

  <!-- Used to submit the form with the enter key -->
  <input type="submit" class="d-none"/>
  <input type="hidden" name="filtering" value=""/>
';
  $groupSql = '';
  if (canView('Groups')) {
    $GroupsById = array();
    foreach (ZM\Group::find() as $G) {
      $GroupsById[$G->Id()] = $G;
    }

    if (count($GroupsById)) {
      $html .= '<span class="term" id="groupControl"><label>'. translate('Group') .'</label>';
      $html .= '<span class="term-value-wrapper">';
      # This will end up with the group_id of the deepest selection
      $group_id = isset($_SESSION['GroupId']) ? $_SESSION['GroupId'] : null;
      $html .= ZM\Group::get_group_dropdown($view);
      $groupSql = ZM\Group::get_group_sql($group_id);
      $html .= addButtonResetForFilterSelect('GroupId[]');
      $html .= '</span>';
      $html .= '</span>';
    }
  }

  $selected_monitor_ids = isset($_SESSION['MonitorId']) ? $_SESSION['MonitorId'] : array();
  if ( !is_array($selected_monitor_ids) ) {
    $selected_monitor_ids = array($selected_monitor_ids);
  }

  $conditions = array();
  $values = array();

  if ( $groupSql )
    $conditions[] = $groupSql;
  foreach ( array('ServerId','StorageId','Status','Capturing','Analysing','Recording') as $filter ) {
    if ( isset($_SESSION[$filter]) ) {
      if ( is_array($_SESSION[$filter]) ) {
        $conditions[] = '`'.$filter . '` IN ('.implode(',', array_map(function(){return '?';}, $_SESSION[$filter])). ')';
        $values = array_merge($values, $_SESSION[$filter]);
      } else {
        $conditions[] = '`'.$filter . '`=?';
        $values[] = $_SESSION[$filter];
      }
    }
  } # end foreach filter

  if (count($user->unviewableMonitorIds()) ) {
    $ids = $user->viewableMonitorIds();
    $conditions[] = 'M.Id IN ('.implode(',',array_map(function(){return '?';}, $ids)).')';
    $values = array_merge($values, $ids);
  }

  $html .= '<span class="term MonitorNameFilter"><label>'.translate('Name').'</label>';
  $html .= '<span class="term-value-wrapper">';
  $html .= '<input type="text" name="MonitorName" value="'.(isset($_SESSION['MonitorName'])?validHtmlStr($_SESSION['MonitorName']):'').'" placeholder="'.translate('text or regular expression').'"/></span>';
  $html .= '</span>'.PHP_EOL;

  $html .= addFilterSelect('Capturing', array('None'=>translate('None'), 'Always'=>translate('Always'), 'OnDemand'=>translate('On Demand')));
  $html .= addFilterSelect('Analysing', array('None'=>translate('None'), 'Always'=>translate('Always')));
  $html .= addFilterSelect('Recording', array('None'=>translate('None'), 'OnMotion'=>translate('On Motion'),'Always'=>translate('Always')));

  if ( count($ServersById) > 1 ) {
    $html .= '<span class="term ServerFilter"><label>'. translate('Server').'</label>';
    $html .= '<span class="term-value-wrapper">';
    $html .= htmlSelect('ServerId[]', $ServersById,
      (isset($_SESSION['ServerId'])?$_SESSION['ServerId']:''),
      array(
        'data-on-change'=>$onChangeFunction,
        'class'=>'chosen',
        'multiple'=>'multiple',
        'data-placeholder'=>'All',
      )
    );
    $html .= addButtonResetForFilterSelect('ServerId[]');
    $html .= '</span>';
    $html .= '</span>';
  } # end if have Servers

  if ( count($StorageById) > 1 ) {
    $html .= '<span class="term StorageFilter"><label>'.translate('Storage').'</label>';
    $html .= '<span class="term-value-wrapper">';
    $html .= htmlSelect('StorageId[]', $StorageById,
      (isset($_SESSION['StorageId'])?$_SESSION['StorageId']:''),
      array(
        'data-on-change'=>$onChangeFunction,
        'class'=>'chosen',
        'multiple'=>'multiple',
        'data-placeholder'=>'All',
      ) );
    $html .= addButtonResetForFilterSelect('StorageId[]');
    $html .= '</span>';
    $html .= '</span>';
  } # end if have Storage Areas

  $html .= '<span class="term StatusFilter"><label>'.translate('Status').'</label>';
  $status_options = array(
    'Unknown' => translate('StatusUnknown'),
    'NotRunning' => translate('StatusNotRunning'),
    'Running' => translate('StatusRunning'),
    'Connected' => translate('StatusConnected'),
    );
  $html .= '<span class="term-value-wrapper">';
  $html .= htmlSelect( 'Status[]', $status_options,
    ( isset($_SESSION['Status']) ? $_SESSION['Status'] : '' ),
    array(
      'data-on-change'=>$onChangeFunction,
      'class'=>'chosen',
      'multiple'=>'multiple',
      'data-placeholder'=>'All'
    ) );
  $html .= addButtonResetForFilterSelect('Status[]');
  $html .= '</span>';
  $html .= '</span>';

  $html .= '<span class="term SourceFilter"><label>'.translate('Source').'</label>';
  $html .= '<span class="term-value-wrapper">';
  $html .= '<input type="text" name="Source" value="'.(isset($_SESSION['Source'])?validHtmlStr($_SESSION['Source']):'').'" placeholder="'.translate('text or regular expression').'"/>';
  $html .= '</span>';
  $html .= '</span>';

  $sqlAll = 'SELECT M.*, S.*, E.*
    FROM Monitors AS M
    LEFT JOIN Monitor_Status AS S ON S.MonitorId=M.Id 
    LEFT JOIN Event_Summaries AS E ON E.MonitorId=M.Id 
    WHERE M.`Deleted`=false';
  $sqlSelected = $sqlAll . ( count($conditions) ? ' AND ' . implode(' AND ', $conditions) : '' ).' ORDER BY Sequence ASC';
  $monitors = dbFetchAll($sqlSelected, null, $values);

  $colAllAvailableMonitors = 0;
  foreach ( dbFetchAll($sqlAll) as $row ) {
    if ( visibleMonitor($row['Id']) ) { #We count only available monitors.
      ++$colAllAvailableMonitors;
    }
  }
  
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
      #ZM\Debug('Monitor '.$monitors[$i]['Id'].' is not visible');
      continue;
    }

    if ( isset($_SESSION['MonitorName']) ) {
      $Monitor = new ZM\Monitor($monitors[$i]);
      ini_set('track_errors', 'on');
      $php_errormsg = '';
      $regexp = $_SESSION['MonitorName'];
      if (!strpos($regexp, '/')) $regexp = '/'.$regexp.'/i';

      @preg_match($regexp, '');
      if ( $php_errormsg ) {
        $regexp = '/'.preg_quote($regexp,'/').'/i';
      }

      if ( !@preg_match($regexp, $Monitor->Name()) ) {
        continue;
      }
    }

    if ( isset($_SESSION['Source']) ) {
      $Monitor = new ZM\Monitor($monitors[$i]);
      ini_set('track_errors', 'on');
      $php_errormsg = '';
      $regexp = $_SESSION['Source'];

      if (!preg_match("/^\/.+\/[a-z]*$/i",$regexp))
        $regexp = '/'.$regexp.'/i';

      @preg_match($regexp, '');
      if ( $php_errormsg ) {
        ZM\Warning($_SESSION['Source'].' is not a valid search string');
      } else {
        ZM\Debug("Using $regexp for source");
        if ( !preg_match($regexp, $Monitor->Source()) ) {
          ZM\Debug("Source didn't match $regexp ".$Monitor->Source());
          if ( !preg_match($regexp, $Monitor->Path()) ) {
            continue;
          }
        }
      }
    }

    $monitors_dropdown[$monitors[$i]['Id']] = $monitors[$i]['Id'].' '.$monitors[$i]['Name'];

    if ( count($selected_monitor_ids) and ! in_array($monitors[$i]['Id'], $selected_monitor_ids) ) {
      continue;
    }
    $displayMonitors[] = $monitors[$i];
  } # end foreach monitor
  $html .= '<span class="term MonitorFilter"><label>'.translate('Monitor').'</label>';
  $html .= '<span class="term-value-wrapper">';
  $html .= htmlSelect('MonitorId[]', $monitors_dropdown, $selected_monitor_ids,
    array(
      'data-on-change'=>$onChangeFunction,
      'class'=>'chosen',
      'multiple'=>'multiple',
      'data-placeholder'=>'All',
    ) );
  # Repurpose this variable to be the list of MonitorIds as a result of all the filtering
  $display_monitor_ids = array_map(function($monitor_row){return $monitor_row['Id'];}, $displayMonitors);
  $html .= addButtonResetForFilterSelect('MonitorId[]');
  $html .= '</span>';
  $html .= '</span>';
  $html .= '</div>';

  return [
    "filterBar" => $html,
    "displayMonitors" => $displayMonitors,
    "storage_areas" => $storage_areas, //Console page
    "StorageById" => $StorageById, //Console page
    "colAllAvailableMonitors" => $colAllAvailableMonitors, //Console page
    "selected_monitor_ids" => $selected_monitor_ids
  ];
}
?>
