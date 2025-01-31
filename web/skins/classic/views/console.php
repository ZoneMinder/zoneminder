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
$maxWidth = 0;
$maxHeight = 0;
$zoneCount = 0;
$total_capturing_bandwidth=0;

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

  if ( $monitor['Capturing'] != 'None' ) {
    $scaleWidth = reScale($monitor['Width'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE);
    $scaleHeight = reScale($monitor['Height'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE);
    if ( $maxWidth < $scaleWidth ) $maxWidth = $scaleWidth;
    if ( $maxHeight < $scaleHeight ) $maxHeight = $scaleHeight;
  }
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
$cycleWidth = $maxWidth;
$cycleHeight = $maxHeight;

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
        
        &nbsp;<a href="#" data-flip-сontrol-object="#fbpanel"><i id="fbflip" class="material-icons" data-icon-visible="filter_alt_off" data-icon-hidden="filter_alt"></i></a>
    
    </div><!-- contentButtons -->
<?php
ob_start();
?>
    <div id="monitorList" class="container-fluid table-responsive-sm">
      <table class="table table-striped table-hover table-condensed consoleTable">
        <thead class="thead-highlight">
          <tr>
<?php if ($canEditMonitors) { ?>
            <th class="colMark"><input type="checkbox" name="toggleCheck" value="1" data-checkbox-name="markMids[]" data-on-click-this="updateFormCheckboxesByName"/></th>
<?php } ?>
<?php if ( ZM_WEB_ID_ON_CONSOLE ) { ?>
            <th class="colId"><?php echo translate('Id') ?></th>
<?php } ?>
            <th class="colName"><i class="material-icons">videocam</i>&nbsp;<?php echo translate('Name') ?></th>
            <th class="colFunction"><?php echo translate('Function') ?></th>
<?php if ( count($Servers) ) { ?>
            <th class="colServer"><?php echo translate('Server') ?></th>
<?php } ?>
            <th class="colSource"><i class="material-icons">settings</i>&nbsp;<?php echo translate('Source') ?></th>
<?php if ( $show_storage_areas ) { ?>
            <th class="colStorage"><?php echo translate('Storage') ?></th>
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
    echo '<th class="colEvents"><a '
      .(canView('Events') ? 'href="?view='.ZM_WEB_EVENTS_VIEW.'&amp;page=1'.$filter['querystring'].'">' : '')
      .$eventCounts[$i]['title']
      .'</a></th>'.PHP_EOL;
  } // end foreach eventCounts
?>
            <th class="colZones"><a href="?view=zones"><?php echo translate('Zones') ?></a></th>
          </tr>
        </thead>
        <tbody id="consoleTableBody">
<?php
$table_head = ob_get_contents();
ob_end_clean();
echo $table_head;

$group_ids_by_monitor_id = array();
foreach (ZM\Group_Monitor::find(array('MonitorId'=>$displayMonitorIds)) as $GM) {
  if ( !isset($group_ids_by_monitor_id[$GM->MonitorId()]) )
    $group_ids_by_monitor_id[$GM->MonitorId()] = array();
  $group_ids_by_monitor_id[$GM->MonitorId()][] = $GM->GroupId();
}
$monitors = array();
for ($monitor_i = 0; $monitor_i < count($displayMonitors); $monitor_i += 1) {
  $monitor = $displayMonitors[$monitor_i];
  $Monitor = new ZM\Monitor($monitor);
  $monitors[] = $Monitor;
  $Monitor->GroupIds(isset($group_ids_by_monitor_id[$Monitor->Id()]) ? $group_ids_by_monitor_id[$Monitor->Id()] : array());
  if ( $monitor_i and ( $monitor_i % 200 == 0 ) ) {
    echo '</table>';
    echo $table_head;
  } # monitor_i % 200
?>
          <tr id="<?php echo 'monitor_id-'.$monitor['Id'] ?>" title="<?php echo $monitor['Id'] ?>">
<?php
  $source_class = 'infoText';
  $source_class_reason = '';

  # 1 minute + fps_report_interval should be plenty.  
  $fps_report_seconds = 60+($monitor['FPSReportInterval'] * $monitor['CaptureFPS']);
  if ( (!$monitor['Status'] || ($monitor['Status'] == 'NotRunning')) && ($monitor['Type'] != 'WebSite')) {
    $source_class = 'errorText';
    $source_class_reason = translate('Not Running');
  } else if ((!$monitor['UpdatedOn']) or (strtotime($monitor['UpdatedOn']) < time()-$fps_report_seconds)) {
    $source_class = 'errorText';
    $source_class_reason = translate('Offline');
  } else {
    if ( $monitor['CaptureFPS'] == '0.00' ) {
      $source_class = 'errorText';
      $source_class_reason = translate('No capture FPS');
    } else if ( (!$monitor['AnalysisFPS']) && ($monitor['Analysing'] != 'None') ) {
      $source_class = 'warnText';
      $source_class_reason = translate('No analysis FPS');
    }
  }

  $function_class = 'infoText';

  $dot_class = $source_class;
  $dot_class_reason = $source_class_reason;
  if ( $function_class != 'infoText' ) {
    $dot_class = $function_class;
  #} else if (($monitor['Analysing'] == 'Always') and !$monitor['Enabled']) {
    #$dot_class .= ' warnText';
    #$dot_class_reason .= ' '.translate('Analysis is disabled');
    #FIXME replace this with a check for runstate vs dbstate
  }

  $scale = max(reScale(SCALE_BASE, $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE), SCALE_BASE);
  $stream_available = canView('Stream') and $monitor['Type']=='WebSite' or ($monitor['CaptureFPS'] && $monitor['Capturing'] != 'None');

  if ($canEditMonitors) {
?>
            <td class="colMark">
              <input type="checkbox" name="markMids[]" value="<?php echo $monitor['Id'] ?>" data-on-click-this="setButtonStates"/>
            </td>
<?php
  }
  if (ZM_WEB_ID_ON_CONSOLE) {
?>
            <td class="colId"><a <?php echo ($stream_available ? 'href="?view=watch&amp;mid='.$monitor['Id'].'">' : '>') . $monitor['Id'] ?></a></td>
<?php
  }
  $imgHTML = '';
  if (ZM_WEB_LIST_THUMBS && ($monitor['Capturing'] != 'None') && canView('Stream')) {
    $options = array();

    $ratio_factor = $Monitor->ViewWidth() ? $Monitor->ViewHeight() / $Monitor->ViewWidth() : 1;
    $options['width'] = ZM_WEB_LIST_THUMB_WIDTH;
    $options['height'] = ZM_WEB_LIST_THUMB_HEIGHT ? ZM_WEB_LIST_THUMB_HEIGHT : ZM_WEB_LIST_THUMB_WIDTH*$ratio_factor;
    $options['scale'] = $Monitor->ViewWidth() ? intval(100*ZM_WEB_LIST_THUMB_WIDTH / $Monitor->ViewWidth()) : 100;
    $options['mode'] = 'jpeg';
    $options['frames'] = 1;

    $stillSrc = $Monitor->getStreamSrc($options);
    $streamSrc = $Monitor->getStreamSrc(array('scale'=>$options['scale']*5));

    $thmbWidth = ( $options['width'] ) ? 'width:'.$options['width'].'px;' : '';
    $thmbHeight = ( $options['height'] ) ? 'height:'.$options['height'].'px;' : '';
    
    $imgHTML = '<div class="colThumbnail" style="'.$thmbHeight.'"><a';
    $imgHTML .= $stream_available ? ' href="?view=watch&amp;mid='.$monitor['Id'].'">' : '>';
    $imgHTML .= '<img id="thumbnail' .$Monitor->Id(). '" src="' .$stillSrc. '" style="'
      .$thmbWidth.$thmbHeight. '" stream_src="' .$streamSrc. '" still_src="' .$stillSrc. '"'.
      ($options['width'] ? ' width="'.$options['width'].'"' : '' ).
      ($options['height'] ? ' height="'.$options['height'].'"' : '' ).
      ' loading="lazy" /></a></div>';
  }
?>
            <td class="colName">
            <i class="material-icons <?php echo $dot_class ?>" title="<?php echo $dot_class_reason ?>">lens</i>
              <a <?php echo ($stream_available ? 'href="?view=watch&amp;mid='.$monitor['Id'].'">' : '>') . validHtmlStr($monitor['Name']) ?></a><br/>
              <?php echo $imgHTML ?>
              <div class="small text-nowrap text-muted">

<?php 
  if (canView('Groups')) {
    echo implode('<br/>',
                  array_map(function($group_id){
                    $Group = ZM\Group::find_one(array('Id'=>$group_id));
                    if ( $Group ) {
                      $Groups = $Group->Parents();
                      array_push( $Groups, $Group );
                    }
                    return implode(' &gt; ', array_map(function($Group){
                      if (canView('Stream')) {
                        return '<a href="?view=montagereview&amp;GroupId='.$Group->Id().'">'.validHtmlStr($Group->Name()).'</a>';
                      } else {
                        return validHtmlStr($Group->Name());
                      }
                    }, $Groups ));
                  }, $Monitor->GroupIds()));
  }
?>
            </div></td>
            <td class="colFunction">
              <!--<a class="functionLnk <?php echo $function_class ?>" data-mid="<?php echo $monitor['Id'] ?>" id="functionLnk-<?php echo $monitor['Id'] ?>" href="#"><?php echo translate('Fn'.$monitor['Function']) ?></a>-->
<?php
  if ((!$monitor['UpdatedOn']) or (strtotime($monitor['UpdatedOn']) < time()-$fps_report_seconds)) {
    echo translate('Offline').'<br/>';
  } else {
    echo translate('Status'.$monitor['Status']).'<br/>';
    if ($monitor['Analysing'] != 'None') {
      echo translate('Analysing') . ': '.translate($monitor['Analysing']).'<br/>';
    }
    if ($monitor['Recording'] != 'None') {
      echo translate('Recording') . ': '.translate($monitor['Recording']) . ($monitor['ONVIF_Event_Listener'] ? ' Use ONVIF' : "") . '<br/>';
    }
 ?><br/>
              <div class="small text-nowrap text-muted">
<?php 
    $fps_string = '';
    if (isset($monitor['CaptureFPS'])) {
      $fps_string .= $monitor['CaptureFPS'];
    }

    if ( isset($monitor['AnalysisFPS']) and ($monitor['Analysing'] != 'None')) {
      $fps_string .= '/' . $monitor['AnalysisFPS'];
    }
    if ($fps_string) $fps_string .= ' fps';
    if (!empty($monitor['CaptureBandwidth']))
      $fps_string .= ' ' . human_filesize($monitor['CaptureBandwidth']).'/s';
    $total_capturing_bandwidth += $monitor['CaptureBandwidth'];
    echo $fps_string;
    echo '</div>';
  } # end if offline
  echo '</td>'.PHP_EOL;
  if (count($Servers)) {
    $Server = isset($ServersById[$monitor['ServerId']]) ? $ServersById[$monitor['ServerId']] : new ZM\Server($monitor['ServerId']);
    echo '<td class="colServer">'.validHtmlStr($Server->Name()).'</td>'.PHP_EOL;
  }
  echo '<td class="colSource">'. makeLink( '?view=monitor&amp;mid='.$monitor['Id'], '<span class="'.$source_class.'">'.validHtmlStr($Monitor->Source()).'</span>', $Monitor->canEdit()).'</td>';
  if ($show_storage_areas) {
    echo '<td class="colStorage">'.
      (isset($StorageById[$monitor['StorageId']]) ? validHtmlStr($StorageById[$monitor['StorageId']]->Name()) : ($monitor['StorageId']?'<span class="error">Deleted '.$monitor['StorageId'].'</span>' : '')).'</td>'.PHP_EOL;
  }

  foreach (array_keys($eventCounts) as $i) {
    echo '<td class="colEvents"><a '. (canView('Events') ? 'href="?view='.ZM_WEB_EVENTS_VIEW.'&amp;page=1'.$monitor['eventCounts'][$i]['filter']['querystring'].'">'  : '') . 
      (int)$monitor[$i.'Events'] . '<br/></a><div class="small text-nowrap text-muted">' . human_filesize($monitor[$i.'EventDiskSpace']).'</div></td>'.PHP_EOL;
  }
  echo '<td class="colZones">'. makeLink('?view=zones&amp;mid='.$monitor['Id'], $monitor['ZoneCount'], canView('Monitors')) .'</td>'.PHP_EOL;
?>
          </tr>
<?php
} # end for each monitor
?>
        </tbody>
        <tfoot>
          <tr>
<?php if ($canEditMonitors) { ?>
            <td class="colMark"></td>
<?php } ?>
<?php if ( ZM_WEB_ID_ON_CONSOLE ) { ?>
            <td class="colId"><?php echo translate('Total').":".count($displayMonitors) ?></td>
<?php } ?>
            <td class="colName"></td>
            <td class="colFunction"><?php echo human_filesize($total_capturing_bandwidth ).'/s' ?></td>
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
