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

if ( $running == null ) 
  $running = daemonCheck();

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
          array( 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-1 hour' ),
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
          array( 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-1 day' ),
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
          array( 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-7 day' ),
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
          array( 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-1 month' ),
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
          array( 'attr' => 'Archived', 'op' => '=', 'val' => '1' ),
        )
      )
    ),
    'totalevents' => 0,
    'totaldiskspace' => 0,
  ),
);


$navbar = getNavBarHTML();
ob_start();
include('_monitor_filters.php');
$filterbar = ob_get_contents();
ob_end_clean();

$show_storage_areas = count($storage_areas) > 1 and canEdit( 'System' ) ? 1 : 0;
$maxWidth = 0;
$maxHeight = 0;
$zoneCount = 0;
$total_capturing_bandwidth=0;

$status_counts = array();
for ( $i = 0; $i < count($displayMonitors); $i++ ) {
  $monitor = &$displayMonitors[$i];
  if ( ! $monitor['Status'] ) {
    if ( $monitor['Type'] == 'WebSite' )
     $monitor['Status'] = 'Running';
    else
     $monitor['Status'] = 'NotRunning';
  }
  if ( !isset($status_counts[$monitor['Status']]) )
    $status_counts[$monitor['Status']] = 0;
  $status_counts[$monitor['Status']] += 1;

  if ( $monitor['Function'] != 'None' ) {
    $scaleWidth = reScale( $monitor['Width'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
    $scaleHeight = reScale( $monitor['Height'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
    if ( $maxWidth < $scaleWidth ) $maxWidth = $scaleWidth;
    if ( $maxHeight < $scaleHeight ) $maxHeight = $scaleHeight;
  }
  #$monitor['zmc'] = zmcStatus( $monitor );
  #$monitor['zma'] = zmaStatus( $monitor );
  $zoneCount += $monitor['ZoneCount'];

  $counts = array();
  foreach ( array_keys( $eventCounts ) as $j ) {
    $filter = addFilterTerm(
      $eventCounts[$j]['filter'],
      count($eventCounts[$j]['filter']['Query']['terms']),
      array( 'cnj' => 'and', 'attr' => 'MonitorId', 'op' => '=', 'val' => $monitor['Id'] )
    );
    parseFilter( $filter );
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
if ( count($servers) ) $left_columns += 1;
if ( ZM_WEB_ID_ON_CONSOLE ) $left_columns += 1;
if ( $show_storage_areas ) $left_columns += 1;


xhtmlHeaders( __FILE__, translate('Console') );
?>
<body>
  <form name="monitorForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
    <input type="hidden" name="view" value="<?php echo $view ?>"/>
    <input type="hidden" name="action" value=""/>

    <?php echo $navbar ?>
    <div class="filterBar"><?php echo $filterbar ?></div>
    <div class="statusBreakdown">
<?php
  $html = '';
  foreach ( array_keys($status_counts) as $status ) {
      
    $html .= '<span class="status"><label>'.translate('Status'.$status).'</label>'.round(100*($status_counts[$status]/count($displayMonitors)),1).'%</span>';
  }
  echo $html;
?>
    </div>

    <div class="container-fluid">
      <button type="button" name="addBtn" onclick="addMonitor(this);"
      <?php echo (canEdit('Monitors') && !$user['MonitorIds']) ? '' : ' disabled="disabled"' ?>
      >
      <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>&nbsp;<?php echo translate('AddNewMonitor') ?>
      </button>
      <button type="button" name="cloneBtn" onclick="cloneMonitor(this);"
      <?php echo (canEdit('Monitors') && !$user['MonitorIds']) ? '' : ' disabled="disabled"' ?>
      style="display:none;">
      <span class="glyphicon glyphicon-copy"></span>&nbsp;<?php echo translate('CloneMonitor') ?>
      </button>
      <button type="button" name="editBtn" onclick="editMonitor(this);" disabled="disabled">
      <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>&nbsp;<?php echo translate('Edit') ?>
      </button>
      <button type="button" name="deleteBtn" onclick="deleteMonitor(this);" disabled="disabled">
      <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>&nbsp;<?php echo translate('Delete') ?>
      </button>
      <button type="button" name="selectBtn" onclick="selectMonitor(this);" disabled="disabled"><?php echo translate('Select')?></button>
<?php
ob_start();
?>
      <table class="table table-striped table-hover table-condensed consoleTable">
        <thead class="thead-highlight">
          <tr>
<?php if ( ZM_WEB_ID_ON_CONSOLE ) { ?>
            <th class="colId"><?php echo translate('Id') ?></th>
<?php } ?>
            <th class="colName"><i class="material-icons md-18">videocam</i>&nbsp;<?php echo translate('Name') ?></th>
            <th class="colFunction"><?php echo translate('Function') ?></th>
<?php if ( count($servers) ) { ?>
            <th class="colServer"><?php echo translate('Server') ?></th>
<?php } ?>
            <th class="colSource"><i class="material-icons md-18">settings</i>&nbsp;<?php echo translate('Source') ?></th>
<?php if ( $show_storage_areas ) { ?>
            <th class="colStorage"><?php echo translate('Storage') ?></th>
<?php }
      foreach ( array_keys($eventCounts) as $j ) {
        echo '<th class="colEvents">'. $j .'</th>';
      }
?>
            <th class="colZones"><?php echo translate('Zones') ?></th>
<?php if ( canEdit('Monitors') ) { ?>
            <th class="colMark"><input type="checkbox" name="toggleCheck" value="1" onclick="toggleCheckbox(this, 'markMids[]');setButtonStates(this);"/> <?php echo translate('All') ?></th>
<?php } ?>
          </tr>
        </thead>
        <tbody id="consoleTableBody">
<?php
$table_head = ob_get_contents();
ob_end_clean();
echo $table_head;
for( $monitor_i = 0; $monitor_i < count($displayMonitors); $monitor_i += 1 ) {
  $monitor = $displayMonitors[$monitor_i];
  $Monitor = new Monitor($monitor);

  if ( $monitor_i and ( $monitor_i % 100 == 0 ) ) {
    echo '</table>';
    echo $table_head;
  } # monitor_i % 100
?>
          <tr id="<?php echo 'monitor_id-'.$monitor['Id'] ?>" title="<?php echo $monitor['Id'] ?>">
<?php
  if ( (!$monitor['Status'] || $monitor['Status'] == 'NotRunning') && $monitor['Type']!='WebSite' ) {
    $source_class = 'errorText';
  } else {
    if ( $monitor['CaptureFPS'] == '0.00' ) {
      $source_class = 'errorText';
    } else if ( (!$monitor['AnalysisFPS']) && ($monitor['Function']!='Monitor') && ($monitor['Function'] != 'Nodect') ) {
      $source_class = 'warnText';
    } else {
      $source_class = 'infoText';
    }
  }
  if ( $monitor['Function'] == 'None' )
    $fclass = 'errorText';
  else
    $fclass = 'infoText';
  if ( !$monitor['Enabled'] )
    $fclass .= ' disabledText';
  $scale = max(reScale(SCALE_BASE, $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE), SCALE_BASE);
  $stream_available = canView('Stream') and $monitor['Type']=='WebSite' or ($monitor['CaptureFPS'] && $monitor['Function'] != 'None');
  $dot_class=$source_class;
  if ( $fclass != 'infoText' ) $dot_class=$fclass;

  if ( ZM_WEB_ID_ON_CONSOLE ) {
?>
            <td class="colId"><a <?php echo ($stream_available ? 'href="?view=watch&amp;mid='.$monitor['Id'].'">' : '>') . $monitor['Id'] ?></a></td>
<?php
  }
?>
            <td class="colName">
              <span class="glyphicon glyphicon-dot <?php echo $dot_class ?>"  aria-hidden="true"></span><a <?php echo ($stream_available ? 'href="?view=watch&amp;mid='.$monitor['Id'].'">' : '>') . $monitor['Name'] ?></a><br/><div class="small text-nowrap text-muted">
              <?php echo implode('<br/>',
                  array_map(function($group_id){
                    $Group = Group::find_one(array('Id'=>$group_id));
                    if ( $Group ) {
                      $Groups = $Group->Parents();
                      array_push( $Groups, $Group );
                    }
                    return implode(' &gt; ', array_map(function($Group){ return '<a href="'. ZM_BASE_URL.$_SERVER['PHP_SELF'].'?view=montagereview&GroupId='.$Group->Id().'">'.$Group->Name().'</a>'; }, $Groups ));
                    }, $Monitor->GroupIds() ) ); 
?>
            </div></td>
            <td class="colFunction">
              <?php echo makePopupLink( '?view=function&amp;mid='.$monitor['Id'], 'zmFunction', 'function', '<span class="'.$fclass.'">'.translate('Fn'.$monitor['Function']).( empty($monitor['Enabled']) ? ', disabled' : '' ) .'</span>', canEdit( 'Monitors' ) ) ?><br/>
              <?php echo translate('Status'.$monitor['Status']) ?><br/>
              <div class="small text-nowrap text-muted">
<?php 
  $fps_string = '';
  if ( isset($monitor['CaptureFPS']) ) {
    $fps_string .= $monitor['CaptureFPS'];
  }

  if ( isset($monitor['AnalysisFPS']) and ( $monitor['Function'] == 'Mocord' or $monitor['Function'] == 'Modect' ) ) {
    $fps_string .= '/' . $monitor['AnalysisFPS'];
  }
  if ($fps_string) $fps_string .= ' fps';
  $fps_string .= ' ' . human_filesize($monitor['CaptureBandwidth']).'/s';
  $total_capturing_bandwidth += $monitor['CaptureBandwidth'];
  echo $fps_string;
?>
              </div></td>
<?php
  if ( count($servers) ) { ?>
            <td class="colServer"><?php $Server = isset($ServersById[$monitor['ServerId']]) ? $ServersById[$monitor['ServerId']] : new Server( $monitor['ServerId'] ); echo $Server->Name(); ?></td>
<?php
  }
  echo '<td class="colSource">'. makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$source_class.'">'.$Monitor->Source().'</span>', canEdit('Monitors') ).'</td>';
  if ( $show_storage_areas ) {
?>
            <td class="colStorage"><?php if ( isset($StorageById[$monitor['StorageId']]) ) { echo $StorageById[ $monitor['StorageId'] ]->Name(); } ?></td>
<?php
  }

      foreach ( array_keys($eventCounts) as $i ) {
?>
            <td class="colEvents"><a <?php echo (canView('Events') ? 'href="?view='.ZM_WEB_EVENTS_VIEW.'&amp;page=1'.$monitor['eventCounts'][$i]['filter']['query'].'">'  : '') . 
                $monitor[$i.'Events'] . '<br/></a><div class="small text-nowrap text-muted">' . human_filesize($monitor[$i.'EventDiskSpace']) ?></div></td>
<?php
  }
?>
            <td class="colZones"><?php echo makePopupLink('?view=zones&amp;mid='.$monitor['Id'], 'zmZones', array('zones', $monitor['Width'], $monitor['Height']), $monitor['ZoneCount'], canView('Monitors')) ?></td>
<?php
  if ( canEdit('Monitors') ) {
?>
            <td class="colMark">
              <input type="checkbox" name="markMids[]" value="<?php echo $monitor['Id'] ?>" onclick="setButtonStates( this )"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/>
              <span class="glyphicon glyphicon-sort"></span>
            </td>
<?php
  }
?>
          </tr>
<?php
} # end for each monitor
?>
        </tbody>
        <tfoot>
          <tr>
<?php if ( ZM_WEB_ID_ON_CONSOLE ) { ?>
            <td class="colId"><?php echo translate('Total').":".count($displayMonitors) ?></td>
<?php } ?>
            <td class="colName"></td>
            <td class="colFunction"><?php echo human_filesize($total_capturing_bandwidth ).'/s' ?></td>
<?php if ( count($servers) ) { ?>
            <td class="colServer"></td>
<?php } ?>
            <td class="colSource"></td>
<?php if ( $show_storage_areas ) { ?>
            <td class="colStorage"></td>
<?php
}
  foreach ( array_keys( $eventCounts ) as $i ) {
    parseFilter( $eventCounts[$i]['filter'] );
?>
            <td class="colEvents">
              <a <?php echo (canView('Events') ? 'href="?view='.ZM_WEB_EVENTS_VIEW.'&amp;page=1'.$eventCounts[$i]['filter']['query'].'">' : '') . 
                  $eventCounts[$i]['totalevents'].'<br/></a>'.'<div class="small text-nowrap text-muted">'.human_filesize($eventCounts[$i]['totaldiskspace']) ?></td>
<?php
      }
?>
            <td class="colZones"><?php echo $zoneCount ?></td>
<?php if ( canEdit('Monitors') ) { ?>
            <td class="colMark"></td>
<?php } ?>
          </tr>
        </tfoot>
      </table>
    </div>
  </form>
<?php xhtmlFooter() ?>
