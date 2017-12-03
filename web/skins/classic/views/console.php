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
          array( 'attr' => 'DateTime', 'op' => '>=', 'val' => '-1 hour' ),
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
for ( $i = 0; $i < count($displayMonitors); $i++ ) {
  $monitor = &$displayMonitors[$i];
  if ( $monitor['Function'] != 'None' ) {
    $scaleWidth = reScale( $monitor['Width'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
    $scaleHeight = reScale( $monitor['Height'], $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
    if ( $maxWidth < $scaleWidth ) $maxWidth = $scaleWidth;
    if ( $maxHeight < $scaleHeight ) $maxHeight = $scaleHeight;
  }
  $monitor['zmc'] = zmcStatus( $monitor );
  $monitor['zma'] = zmaStatus( $monitor );
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
    <?php echo $filterbar ?>

    <div class="container-fluid">
      <table class="table table-striped table-hover table-condensed" id="consoleTable">
        <thead>
          <tr>
<?php if ( ZM_WEB_ID_ON_CONSOLE ) { ?>
            <th class="colId"><?php echo translate('Id') ?></th>
<?php } ?>
            <th class="colName"><?php echo translate('Name') ?></th>
            <th class="colFunction"><?php echo translate('Function') ?></th>
<?php if ( count($servers) ) { ?>
            <th class="colServer"><?php echo translate('Server') ?></th>
<?php } ?>
            <th class="colSource"><?php echo translate('Source') ?></th>
<?php if ( $show_storage_areas ) { ?>
            <th class="colStorage"><?php echo translate('Storage') ?></th>
<?php }
      foreach ( array_keys( $eventCounts ) as $j ) {
        echo '<th class="colEvents">'. $j .'</th>';
      }
?>
            <th class="colZones"><a href="<?php echo $_SERVER['PHP_SELF'] ?>?view=zones_overview"><?php echo translate('Zones') ?></a></th>
<?php if ( canEdit('Monitors') ) { ?>
            <th class="colMark"><input type="checkbox" name="toggleCheck" value="1" onclick="toggleCheckbox( this, 'markMids[]' );"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/> <?php echo translate('All') ?></th>
<?php } ?>
          </tr>
        </thead>
        <tbody id="consoleTableBody">
<?php
for( $monitor_i = 0; $monitor_i < count($displayMonitors); $monitor_i += 1 ) {
  $monitor = $displayMonitors[$monitor_i];
?>
          <tr id="<?php echo 'monitor_id-'.$monitor['Id'] ?>" title="<?php echo $monitor['Id'] ?>">
<?php
  if ( !$monitor['zmc'] ) {
    $dclass = 'errorText';
  } else {
  // https://github.com/ZoneMinder/ZoneMinder/issues/1082
    if ( !$monitor['zma'] && $monitor['Function']!='Monitor' )
      $dclass = 'warnText';
    else
      $dclass = 'infoText';
  }
  if ( $monitor['Function'] == 'None' )
    $fclass = 'errorText';
  //elseif ( $monitor['Function'] == 'Monitor' )
   //   $fclass = 'warnText';
  else
    $fclass = 'infoText';
  if ( !$monitor['Enabled'] )
    $fclass .= ' disabledText';
  $scale = max( reScale( SCALE_BASE, $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );
?>
<?php 
  if ( ZM_WEB_ID_ON_CONSOLE ) {
?>
            <td class="colId"><a <?php echo (canView('Stream') && $running && $monitor['Function'] != 'None' ? 'href="?view=watch&amp;mid='.$monitor['Id'].'">' : '>') . $monitor['Id'] ?></a></td>
<?php
  }
?>
            <td class="colName"><a <?php echo (canView('Stream') && $monitor['Function'] != 'None' ? 'href="?view=watch&amp;mid='.$monitor['Id'].'">' : '>') . $monitor['Name'] ?></a></td>
            <td class="colFunction">
              <?php echo makePopupLink( '?view=function&amp;mid='.$monitor['Id'], 'zmFunction', 'function', '<span class="'.$fclass.'">'.translate('Fn'.$monitor['Function']).( empty($monitor['Enabled']) ? ', disabled' : '' ) .'</span>', canEdit( 'Monitors' ) ) ?><br/>
<?php echo $monitor['CaptureFPS'] . ( ( $monitor['Function'] == 'Mocord' or $monitor['Function'] == 'Modect' ) ? ' / ' . $monitor['AnalysisFPS'] : '' ) . ' FPS' ?>
              </td>
<?php
  if ( count($servers) ) { ?>
            <td class="colServer"><?php $Server = isset($ServersById[$monitor['ServerId']]) ? $ServersById[$monitor['ServerId']] : new Server( $monitor['ServerId'] ); echo $Server->Name(); ?></td>
<?php
  }
  $source = '';
  if ( $monitor['Type'] == 'Local' ) {
    $source = $monitor['Device'].' ('.$monitor['Channel'].')';
  } elseif ( $monitor['Type'] == 'Remote' ) {
    $source = preg_replace( '/^.*@/', '', $monitor['Host'] );
  } elseif ( $monitor['Type'] == 'File' || $monitor['Type'] == 'cURL' ) {
    $source = preg_replace( '/^.*\//', '', $monitor['Path'] );
  } elseif ( $monitor['Type'] == 'Ffmpeg' || $monitor['Type'] == 'Libvlc' ) {
    $domain = parse_url( $monitor['Path'], PHP_URL_HOST );
    $source = $domain ? $domain : preg_replace( '/^.*\//', '', $monitor['Path'] );
  }
  if ( $source == '' ) {
    $source = 'Monitor ' . $monitor['Id'];
  }
  echo '<td class="colSource">'. makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.$source.'</span>', canEdit( 'Monitors' ) ).'</td>';
  if ( $show_storage_areas ) {
?>
            <td class="colStorage"><?php if ( isset( $StorageById[ $monitor['StorageId'] ] ) ) { echo $StorageById[ $monitor['StorageId'] ]->Name(); } ?></td>
<?php
  }

      foreach ( array_keys( $eventCounts ) as $i ) {
?>
            <td class="colEvents"><a <?php echo (canView('Events') ? 'href="?view='.ZM_WEB_EVENTS_VIEW.'&amp;page=1'.$monitor['eventCounts'][$i]['filter']['query'].'">'  : '') . 
                $monitor[$i.'Events'] . '<br/>' . human_filesize($monitor[$i.'EventDiskSpace']) ?></a></td>
<?php
  }
?>
            <td class="colZones"><?php echo makePopupLink( '?view=zones&amp;mid='.$monitor['Id'], 'zmZones', array( 'zones', $monitor['Width'], $monitor['Height'] ), $monitor['ZoneCount'], $running && canView( 'Monitors' ) ) ?></td>
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
            <td class="colId"><?php echo count($displayMonitors) ?></td>
            <td class="colLeftButtons" colspan="<?php echo $left_columns -1?>">
              <input type="button" value="<?php echo translate('Refresh') ?>" onclick="location.reload(true);"/>
              <input type="button" name="addBtn" value="<?php echo translate('AddNewMonitor') ?>" onclick="addMonitor(this);"/>
              <!-- <?php echo makePopupButton( '?view=monitor', 'zmMonitor0', 'monitor', translate('AddNewMonitor'), (canEdit( 'Monitors' ) && !$user['MonitorIds']) ) ?> -->
              <input type="button" name="editBtn" value="<?php echo translate('Edit') ?>" onclick="editMonitor( this )" disabled="disabled"/>
              <input type="button" name="deleteBtn" value="<?php echo translate('Delete') ?>" onclick="deleteMonitor( this )" disabled="disabled"/>
            </td>
<?php
  foreach ( array_keys( $eventCounts ) as $i ) {
    parseFilter( $eventCounts[$i]['filter'] );
?>
            <td class="colEvents">
              <a <?php echo (canView('Events') ? 'href="?view='.ZM_WEB_EVENTS_VIEW.'&amp;page=1'.$eventCounts[$i]['filter']['query'].'">' : '') . 
                  $eventCounts[$i]['totalevents'].'<br/>'.human_filesize($eventCounts[$i]['totaldiskspace']) ?></a></td>
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
