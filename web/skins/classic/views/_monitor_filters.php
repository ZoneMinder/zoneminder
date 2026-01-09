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
  
  // Get selected value from cookie only
  $selectedValue = '';
  if (isset($_REQUEST[$name])) {
    $selectedValue = $_REQUEST[$name];
  } else if (isset($_COOKIE['zmFilter_'.$name])) {
    $cookieValue = $_COOKIE['zmFilter_'.$name];
    if ($cookieValue && $cookieValue !== '') {
      // Try to decode JSON for array values
      $decoded = json_decode($cookieValue, true);
      $selectedValue = ($decoded !== null) ? $decoded : $cookieValue;
    }
  }
  
  $html = '<span class="term '.$name.'Filter"><label>'.translate($name).'</label>';
  $html .= '<span class="term-value-wrapper">';
  $html .= htmlSelect($name.'[]', $options,
    $selectedValue,
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

  // Helper function to get filter value from cookie
  function getFilterFromCookie($var) {
    $cookieName = 'zmFilter_'.$var;
    if (isset($_COOKIE[$cookieName])) {
      $cookieValue = $_COOKIE[$cookieName];
      if ($cookieValue && $cookieValue !== '') {
        // Try to decode JSON for array values
        $decoded = json_decode($cookieValue, true);
        return ($decoded !== null) ? $decoded : $cookieValue;
      }
    }
    return null;
  }

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
      $group_id = getFilterFromCookie('GroupId');
      $html .= ZM\Group::get_group_dropdown($view);
      $groupSql = ZM\Group::get_group_sql($group_id);
      $html .= addButtonResetForFilterSelect('GroupId[]');
      $html .= '</span>';
      $html .= '</span>';
    }
  }

  $selected_monitor_ids = getFilterFromCookie('MonitorId');
  if (!$selected_monitor_ids) {
    $selected_monitor_ids = array();
  } else if (!is_array($selected_monitor_ids)) {
    $selected_monitor_ids = array($selected_monitor_ids);
  }

  $conditions = array();
  $values = array();

  if ( $groupSql )
    $conditions[] = $groupSql;
  foreach ( array('ServerId','StorageId','Status','Capturing','Analysing','Recording') as $filter ) {
    $filterValue = getFilterFromCookie($filter);
    if ( $filterValue ) {
      if ( is_array($filterValue) ) {
        $conditions[] = '`'.$filter . '` IN ('.implode(',', array_map(function(){return '?';}, $filterValue)).')';
        $values = array_merge($values, $filterValue);
      } else {
        $conditions[] = '`'.$filter . '`=?';
        $values[] = $filterValue;
      }
    }
  } # end foreach filter

  if (count($user->unviewableMonitorIds()) ) {
    $ids = $user->viewableMonitorIds();
    $conditions[] = 'M.Id IN ('.implode(',',array_map(function(){return '?';}, $ids)).')';
    $values = array_merge($values, $ids);
  }

  $onChangeFunction = ($view == 'console') ? 'monitorFilterOnChange' : 'submitThisForm';

  $monitorNameValue = getFilterFromCookie('MonitorName');
  $html .= '<span class="term MonitorNameFilter"><label>'.translate('Name').'</label>';
  $html .= '<span class="term-value-wrapper">';
  $html .= '<input type="text" name="MonitorName" value="'.($monitorNameValue ? validHtmlStr($monitorNameValue) : '').'" placeholder="'.translate('text or regular expression').'" data-on-input="'.$onChangeFunction.'"/></span>';
  $html .= '</span>'.PHP_EOL;

  $html .= addFilterSelect('Capturing', array('None'=>translate('None'), 'Always'=>translate('Always'), 'OnDemand'=>translate('On Demand')));
  $html .= addFilterSelect('Analysing', array('None'=>translate('None'), 'Always'=>translate('Always')));
  $html .= addFilterSelect('Recording', array('None'=>translate('None'), 'OnMotion'=>translate('On Motion'),'Always'=>translate('Always')));

  if ( count($ServersById) > 1 ) {
    $html .= '<span class="term ServerFilter"><label>'. translate('Server').'</label>';
    $html .= '<span class="term-value-wrapper">';
    $html .= htmlSelect('ServerId[]', $ServersById,
      getFilterFromCookie('ServerId') ?: '',
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
      getFilterFromCookie('StorageId') ?: '',
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
    getFilterFromCookie('Status') ?: '',
    array(
      'data-on-change'=>$onChangeFunction,
      'class'=>'chosen',
      'multiple'=>'multiple',
      'data-placeholder'=>'All'
    ) );
  $html .= addButtonResetForFilterSelect('Status[]');
  $html .= '</span>';
  $html .= '</span>';

  $sourceValue = getFilterFromCookie('Source');
  $html .= '<span class="term SourceFilter"><label>'.translate('Source').'</label>';
  $html .= '<span class="term-value-wrapper">';
  $html .= '<input type="text" name="Source" value="'.($sourceValue ? validHtmlStr($sourceValue) : '').'" placeholder="'.translate('text or regular expression').'"/>';
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

    $monitorNameFilter = getFilterFromCookie('MonitorName');
    if ( $monitorNameFilter ) {
      $Monitor = new ZM\Monitor($monitors[$i]);
      ini_set('track_errors', 'on');
      $php_errormsg = '';
      $regexp = $monitorNameFilter;
      if (!strpos($regexp, '/')) $regexp = '/'.$regexp.'/i';

      @preg_match($regexp, '');
      if ( $php_errormsg ) {
        $regexp = '/'.preg_quote($regexp,'/').'/i';
      }

      if ( !@preg_match($regexp, $Monitor->Name()) ) {
        continue;
      }
    }

    $sourceFilter = getFilterFromCookie('Source');
    if ( $sourceFilter ) {
      $Monitor = new ZM\Monitor($monitors[$i]);
      ini_set('track_errors', 'on');
      $php_errormsg = '';
      $regexp = $sourceFilter;

      if (!preg_match("/^\/.+\/[a-z]*$/i",$regexp))
        $regexp = '/'.$regexp.'/i';

      @preg_match($regexp, '');
      if ( $php_errormsg ) {
        ZM\Warning($sourceFilter.' is not a valid search string');
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
