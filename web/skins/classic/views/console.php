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

$canEditMonitors = canEdit('Monitors');
$canCreateMonitors = canCreate('Monitors');

$eventCounts = array(
  'Total'=>  array(
    'title' => translate('Events'),
    'filter' => array(
      'Query' => array(
        'terms' => array()
      )
    ),
    'totalevents' => 0,
    'totaldiskspace' => 0,
  ),
  'Hour'=>array(
    'title' => translate('Hour'),
    'filter' => array(
      'Query' => array(
        'terms' => array(
          array( 'cnj'=>'and', 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-1 hour' ),
        )
      )
    ),
    'totalevents' => 0,
    'totaldiskspace' => 0,
  ),
  'Day'=>array(
    'title' => translate('Day'),
    'filter' => array(
      'Query' => array(
        'terms' => array(
          array( 'cnj'=>'and', 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-1 day' ),
        )
      )
    ),
    'totalevents' => 0,
    'totaldiskspace' => 0,
  ),
  'Week'=>array(
    'title' => translate('Week'),
    'filter' => array(
      'Query' => array(
        'terms' => array(
          array( 'cnj'=>'and', 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-7 day' ),
        )
      )
    ),
    'totalevents' => 0,
    'totaldiskspace' => 0,
  ),
  'Month'=>array(
    'title' => translate('Month'),
    'filter' => array(
      'Query' => array(
        'terms' => array(
          array( 'cnj'=>'and', 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-1 month' ),
        )
      )
    ),
    'totalevents' => 0,
    'totaldiskspace' => 0,
  ),
  'Archived'=>array(
    'title' => translate('Archived'),
    'filter' => array(
      'Query' => array(
        'terms' => array(
          array( 'cnj'=>'and', 'attr' => 'Archived', 'op' => '=', 'val' => '1' ),
        )
      )
    ),
    'totalevents' => 0,
    'totaldiskspace' => 0,
  ),
);

require_once('includes/Group_Monitor.php');

$navbar = getNavBarHTML();
include('_monitor_filters.php');
$resultMonitorFilters = buildMonitorsFilters();
$filterbar = $resultMonitorFilters['filterBar'];
$displayMonitors = $resultMonitorFilters['displayMonitors'];
$storage_areas = $resultMonitorFilters['storage_areas'];
$StorageById = $resultMonitorFilters['StorageById'];
$colAllAvailableMonitors = $resultMonitorFilters['selected_monitor_ids'];

$displayMonitorIds = array_map(function($m){return $m['Id'];}, $displayMonitors);

$show_storage_areas = (count($storage_areas) > 1) and (canEdit('System') ? 1 : 0);
$zoneCount = 0;
$total_capturing_bandwidth = 0;
$total_fps = 0;
$total_analysis_fps = 0;

$status_counts = array();
for ( $i = 0; $i < count($displayMonitors); $i++ ) {
  $monitor = &$displayMonitors[$i];
  if ( !$monitor['Status'] ) {
    if ( $monitor['Type'] == 'WebSite' )
     $monitor['Status'] = 'Running';
    else
     $monitor['Status'] = 'NotRunning';
  }
  if ( !isset($status_counts[$monitor['Status']]) )
    $status_counts[$monitor['Status']] = 0;
  $status_counts[$monitor['Status']] += 1;
  $zoneCount += $monitor['ZoneCount'];

  $counts = array();
  foreach ( array_keys($eventCounts) as $j ) {
    $filter = addFilterTerm(
      $eventCounts[$j]['filter'],
      count($eventCounts[$j]['filter']['Query']['terms']),
      array('cnj'=>'and', 'attr'=>'Monitor', 'op'=>'=', 'val'=>$monitor['Id'])
    );
    parseFilter($filter);
    #$counts[] = 'count(if(1'.$filter['sql'].",1,NULL)) AS EventCount$j, SUM(if(1".$filter['sql'].",DiskSpace,NULL)) As DiskSpace$j";
    $monitor['eventCounts'][$j]['filter'] = $filter;
    $eventCounts[$j]['totalevents'] += $monitor[$j.'Events'];
    $eventCounts[$j]['totaldiskspace'] += $monitor[$j.'EventDiskSpace'];
  }
  unset($monitor);
} // end foreach display monitor

noCacheHeaders();

$eventsWindow = 'zm'.ucfirst(ZM_WEB_EVENTS_VIEW);
$left_columns = 3;
if ( count($Servers) ) $left_columns += 1;
if ( ZM_WEB_ID_ON_CONSOLE ) $left_columns += 1;
if ( $show_storage_areas ) $left_columns += 1;

xhtmlHeaders(__FILE__, translate('Console'));
getBodyTopHTML();
echo $navbar ?>
<div id="page">
  <div id="content">
  <form name="monitorForm" method="post" action="?view=<?php echo $view; ?>">
    <input type="hidden" name="action" value=""/>

    <div id="fbpanel" class="filterBar hidden-shift">
      <?php echo $filterbar ?>
    </div>

    <div id="toolbar" class="container-fluid pt-2 pb-2">
      <div class="statusBreakdown">
<?php
  $html = '';
  foreach ( array_keys($status_counts) as $status ) {
      
    $html .= '<span class="status"><label>'.translate('Status'.$status).'</label>'.round(100*($status_counts[$status]/count($displayMonitors)),1).'%</span>';
  }
  echo $html;
?>
      </div>

      <div class="middleButtons">
<?php
  if ($canEditMonitors and (ZM_PATH_ARP or ZM_PATH_ARP_SCAN)) {
?>
        <button type="button" id="scanBtn" title="<?php echo translate('Network Scan') ?>" data-on-click="scanNetwork">
        <i class="material-icons">wifi</i>
        <span class="text"><?php echo translate('Scan Network') ?></span>
        </button>
<?php
  }
?>
        <button type="button" name="addBtn" data-on-click="addMonitor"
        <?php echo $canCreateMonitors ? '' : ' disabled="disabled" title="'.translate('AddMonitorDisabled').'"' ?>
        >
          <i class="material-icons">add_circle</i>
          <span class="text">&nbsp;<?php echo translate('AddNewMonitor') ?></span>
        </button>
        <button type="button" name="cloneBtn" data-on-click-this="cloneMonitor" disabled="disabled">
          <i class="material-icons">content_copy</i>
  <!--content_copy used instead of file_copy as there is a bug in material-icons -->
          <span class="text">&nbsp;<?php echo translate('CloneMonitor') ?></span>
        </button>
        <button type="button" name="editBtn" data-on-click-this="editMonitor" disabled="disabled">
          <i class="material-icons">edit</i>
          <span class="text">&nbsp;<?php echo translate('Edit') ?></span>
        </button>
        <button type="button" name="deleteBtn" data-on-click-this="deleteMonitor" disabled="disabled">
          <i class="material-icons">delete</i>
          <span class="text">&nbsp;<?php echo translate('Delete') ?></span>
        </button>
        <button type="button" name="selectBtn" data-on-click-this="selectMonitor" disabled="disabled">
          <i class="material-icons">view_list</i>
          <span class="text">&nbsp;<?php echo translate('Select') ?></span>
        </button>
      </div>
      <div class="rightButtons">
        <button type="button" id="sortBtn" data-on-click-this="sortMonitors">
        <i class="material-icons sort" title="Click and drag rows to change order">swap_vert</i>
        <span class="text"><?php echo translate('Sort') ?></span>
        </button>
      </div>
        
        &nbsp;<a href="#" data-flip-control-object="#fbpanel"><i id="fbflip" class="material-icons" data-icon-visible="filter_alt_off" data-icon-hidden="filter_alt"></i></a>
    
    </div><!-- contentButtons -->
    
    <div id="monitorList" class="container-fluid table-responsive-sm">
      <table
        id="consoleTable"
        data-locale="<?php echo i18n() ?>"
        data-side-pagination="server"
        data-ajax="ajaxRequest"
        data-pagination="true"
        data-page-size="<?php echo ZM_WEB_EVENTS_PER_PAGE ?>"
        data-page-list="[10, 25, 50, 100, 200, All]"
        data-search="true"
        data-cookie="true"
        data-cookie-same-site="Strict"
        data-cookie-id-table="zmConsoleTable"
        data-cookie-expire="2y"
        data-remember-order="true"
        data-show-columns="true"
        data-show-export="true"
        data-toolbar="#toolbar"
        data-sort-name="Sequence"
        data-sort-order="asc"
        data-show-refresh="true"
        data-click-to-select="true"
        data-maintain-meta-data="true"
        data-buttons-class="btn btn-normal"
        data-mobile-responsive="true"
        class="table table-striped table-hover table-condensed consoleTable"
        style="display:none;"
      >
        <thead class="thead-highlight">
          <tr>
<?php if ($canEditMonitors) { ?>
            <th data-sortable="false" data-field="toggleCheck" data-checkbox="true"></th>
<?php } ?>
<?php if ( ZM_WEB_ID_ON_CONSOLE ) { ?>
            <th data-sortable="true" data-field="Id" class="colId"><?php echo translate('Id') ?></th>
<?php } ?>
<?php if ( ZM_WEB_LIST_THUMBS ) { ?>
            <th data-sortable="false" data-field="Thumbnail" class="colThumbnail"><?php echo translate('Thumbnail') ?></th>
<?php } ?>
            <th data-sortable="true" data-field="Name" class="colName"><i class="material-icons">videocam</i>&nbsp;<?php echo translate('Name') ?></th>
            <th data-sortable="true" data-field="Function" class="colFunction"><?php echo translate('Function') ?></th>
<?php if ( count($Servers) ) { ?>
            <th data-sortable="true" data-field="Server" class="colServer"><?php echo translate('Server') ?></th>
<?php } ?>
            <th data-sortable="true" data-field="Source" class="colSource"><i class="material-icons">settings</i>&nbsp;<?php echo translate('Source') ?></th>
<?php if ( $show_storage_areas ) { ?>
            <th data-sortable="true" data-field="Storage" class="colStorage"><?php echo translate('Storage') ?></th>
<?php }

  foreach ( array_keys($eventCounts) as $i ) {
      $filter = addFilterTerm(
        $eventCounts[$i]['filter'],
        count($eventCounts[$i]['filter']['Query']['terms']),
        count($displayMonitorIds) != $colAllAvailableMonitors #Add monitors to the filter only if the filter limit is set
          ? array(
            'cnj'=>'and',
            'attr'=>'Monitor',
            'op'=>'IN',
            'val'=>implode(',', $displayMonitorIds)
            )
          : ['cnj'=>'and', 'attr'=>'Monitor']
      );
    parseFilter($filter);
    echo '<th data-sortable="true" data-field="'.$i.'Events" class="colEvents"><a '
      .(canView('Events') ? 'href="?view='.ZM_WEB_EVENTS_VIEW.'&amp;page=1'.$filter['querystring'].'">' : '')
      .$eventCounts[$i]['title']
      .'</a></th>'.PHP_EOL;
  } // end foreach eventCounts
?>
            <th data-sortable="true" data-field="ZoneCount" class="colZones"><a href="?view=zones"><?php echo translate('Zones') ?></a></th>
          </tr>
        </thead>
        <tbody id="consoleTableBody">
        </tbody>
        <tfoot>
          <tr>
<?php if ($canEditMonitors) { ?>
            <td class="colMark"></td>
<?php } ?>
<?php if ( ZM_WEB_ID_ON_CONSOLE ) { ?>
            <td class="colId"><?php echo translate('Total').":".count($displayMonitors) ?></td>
<?php } ?>
<?php if ( ZM_WEB_LIST_THUMBS ) { ?>
            <td class="colThumbnail"></td>
<?php } ?>
            <td class="colName"></td>
            <td class="colFunction"><?php echo human_filesize($total_capturing_bandwidth ).'/s '.
$total_fps.' fps / '.$total_analysis_fps.' fps' ?></td>
<?php if ( count($Servers) ) { ?>
            <td class="colServer"></td>
<?php } ?>
            <td class="colSource"></td>
<?php if ( $show_storage_areas ) { ?>
            <td class="colStorage"></td>
<?php
}
  foreach ( array_keys($eventCounts) as $i ) {
    $filter = addFilterTerm(
      $eventCounts[$i]['filter'],
      count($eventCounts[$i]['filter']['Query']['terms']),
      array(
        'cnj'=>'and',
        'attr'=>'Monitor',
        'op'=>'IN',
        'val'=>implode(',', $displayMonitorIds)
        )
    );
    parseFilter($filter);
?>
            <td class="colEvents">
              <a <?php echo
              (canView('Events') ? 'href="?view='.ZM_WEB_EVENTS_VIEW.'&amp;page=1'.$filter['querystring'].'">' : '') . 
              (int)$eventCounts[$i]['totalevents'].'</a><br/>
              <div class="small text-nowrap text-muted">'.human_filesize($eventCounts[$i]['totaldiskspace'])
            ?></div>
            </td>
<?php
  } // end foreach eventCounts
?>
            <td class="colZones"><?php echo $zoneCount ?></td>
         </tr>
        </tfoot>
        </table>
    </div><!-- content table responsive div -->
  </form>
</div><!--content-->
</div><!--page-->
<?php
  xhtmlFooter();
?>
