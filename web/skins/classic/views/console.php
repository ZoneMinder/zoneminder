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

require_once('includes/Server.php');
$servers = Server::find_all();

$eventCounts = array(
    array(
        'title' => translate('Events'),
        'filter' => array(
            'terms' => array(
            )
        ),
    ),
    array(
        'title' => translate('Hour'),
        'filter' => array(
            'terms' => array(
                array( 'attr' => 'DateTime', 'op' => '>=', 'val' => '-1 hour' ),
            )
        ),
    ),
    array(
        'title' => translate('Day'),
        'filter' => array(
            'terms' => array(
                array( 'attr' => "DateTime", 'op' => '>=', 'val' => '-1 day' ),
            )
        ),
    ),
    array(
        'title' => translate('Week'),
        'filter' => array(
            'terms' => array(
                array( 'attr' => "DateTime", 'op' => '>=', 'val' => '-7 day' ),
            )
        ),
    ),
    array(
        'title' => translate('Month'),
        'filter' => array(
            'terms' => array(
                array( 'attr' => "DateTime", 'op' => '>=', 'val' => '-1 month' ),
            )
        ),
    ),
    array(
        'title' => translate('Archived'),
        'filter' => array(
            'terms' => array(
                array( 'attr' => "Archived", 'op' => '=', 'val' => '1' ),
            )
        ),
    ),
);

$running = daemonCheck();
$status = $running?translate('Running'):translate('Stopped');
$run_state = dbFetchOne('select Name from States where  IsActive = 1', 'Name' );

$group = NULL;
if ( ! empty($_COOKIE['zmGroup']) ) {
  if ( $group = dbFetchOne( 'select * from Groups where Id = ?', NULL, array($_COOKIE['zmGroup'])) )
    $groupIds = array_flip(explode( ',', $group['MonitorIds'] ));
}

noCacheHeaders();

$maxWidth = 0;
$maxHeight = 0;
$cycleCount = 0;
$minSequence = 0;
$maxSequence = 1;
$monitors = dbFetchAll( "select * from Monitors order by Sequence asc" );
$displayMonitors = array();
for ( $i = 0; $i < count($monitors); $i++ ) {
  if ( !visibleMonitor( $monitors[$i]['Id'] ) ) {
    continue;
  }
  if ( $group && !empty($groupIds) && !array_key_exists( $monitors[$i]['Id'], $groupIds ) ) {
    continue;
  }
  $monitors[$i]['Show'] = true;
  $monitors[$i]['zmc'] = zmcStatus( $monitors[$i] );
  $monitors[$i]['zma'] = zmaStatus( $monitors[$i] );
  $monitors[$i]['ZoneCount'] = dbFetchOne( 'select count(Id) as ZoneCount from Zones where MonitorId = ?', 'ZoneCount', array($monitors[$i]['Id']) );
  $counts = array();
  for ( $j = 0; $j < count($eventCounts); $j++ ) {
    $filter = addFilterTerm( $eventCounts[$j]['filter'], count($eventCounts[$j]['filter']['terms']), array( 'cnj' => 'and', 'attr' => 'MonitorId', 'op' => '=', 'val' => $monitors[$i]['Id'] ) );
    parseFilter( $filter );
    $counts[] = 'count(if(1'.$filter['sql'].",1,NULL)) as EventCount$j";
    $monitors[$i]['eventCounts'][$j]['filter'] = $filter;
  }
  $sql = 'SELECT '.join($counts,', ').' FROM Events AS E WHERE MonitorId = ?';
  $counts = dbFetchOne( $sql, NULL, array($monitors[$i]['Id']) );
  if ( $monitors[$i]['Function'] != 'None' ) {
    $cycleCount++;
    $scaleWidth = reScale( $monitors[$i]['Width'], $monitors[$i]['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
    $scaleHeight = reScale( $monitors[$i]['Height'], $monitors[$i]['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
    if ( $maxWidth < $scaleWidth ) $maxWidth = $scaleWidth;
    if ( $maxHeight < $scaleHeight ) $maxHeight = $scaleHeight;
  }
  if ( $counts ) $monitors[$i] = array_merge( $monitors[$i], $counts );
  $displayMonitors[] = $monitors[$i];
}

$cycleWidth = $maxWidth;
$cycleHeight = $maxHeight;

$eventsView = ZM_WEB_EVENTS_VIEW;
$eventsWindow = 'zm'.ucfirst(ZM_WEB_EVENTS_VIEW);

$eventCount = 0;
for ( $i = 0; $i < count($eventCounts); $i++ ) {
  $eventCounts[$i]['total'] = 0;
}
$zoneCount = 0;
foreach( $displayMonitors as $monitor ) {
  for ( $i = 0; $i < count($eventCounts); $i++ ) {
    $eventCounts[$i]['total'] += $monitor['EventCount'.$i];
  }
  $zoneCount += $monitor['ZoneCount'];
}

$versionClass = (ZM_DYN_DB_VERSION&&(ZM_DYN_DB_VERSION!=ZM_VERSION))?'errorText':'';

$left_columns = 3;
if ( count($servers) ) $left_columns += 1;
if ( ZM_WEB_ID_ON_CONSOLE ) $left_columns += 1;

xhtmlHeaders( __FILE__, translate('Console') );
?>
<body>
  <div id="page">
    <form name="monitorForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
    <input type="hidden" name="view" value="<?php echo $view ?>"/>
    <input type="hidden" name="action" value=""/>
    <div id="header">
      <h3 id="systemTime"><?php echo preg_match( '/%/', DATE_FMT_CONSOLE_LONG )?strftime( DATE_FMT_CONSOLE_LONG ):date( DATE_FMT_CONSOLE_LONG ) ?></h3>
      <h3 id="systemStats"><?php echo systemStats() ?></h3>
      <h2 id="title">
        <a href="http://www.zoneminder.com" target="ZoneMinder">ZoneMinder</a> <?php echo translate('Console') ?> -
        <?php echo makePopupLink( '?view=state', 'zmState', 'state', $status, canEdit( 'System' ) ) ?> - 
        <?php echo $run_state ?> <?php echo makePopupLink( '?view=version', 'zmVersion', 'version', '<span class="'.$versionClass.'">v'.ZM_VERSION.'</span>', canEdit( 'System' ) ) ?>
      </h2>
      <div class="clear"></div>
      <?php if ( ZM_WEB_CONSOLE_BANNER ) { ?><h3 id="development"><?php echo ZM_WEB_CONSOLE_BANNER ?></h3><?php } ?>
      <div id="monitorSummary"><?php echo makePopupLink( '?view=groups', 'zmGroups', 'groups', sprintf( $CLANG['MonitorCount'], count($displayMonitors), zmVlang( $VLANG['Monitor'], count($displayMonitors) ) ).($group?' ('.$group['Name'].')':''), canView( 'Groups' ) ); ?></div>
<?php
if ( ZM_OPT_X10 && canView( 'Devices' ) ) {
?>
      <div id="devices"><?php echo makePopupLink( '?view=devices', 'zmDevices', 'devices', translate('Devices') ) ?></div>
<?php
}
if ( canView( 'System' ) ) {
?>
      <div id="options"><?php echo makePopupLink( '?view=options', 'zmOptions', 'options', translate('Options') ) ?><?php if ( logToDatabase() > Logger::NOLOG ) { ?> / <?php echo makePopupLink( '?view=log', 'zmLog', 'log', '<span class="'.logState().'">'.translate('Log').'</span>' ) ?><?php } ?></div>
<?php
}
if ( canView( 'Stream' ) && $cycleCount > 1 ) {
    $cycleGroup = isset($_COOKIE['zmGroup'])?$_COOKIE['zmGroup']:0;
?>
      <div id="cycleMontage">
        <?php echo makePopupLink( '?view=cycle&amp;group='.$cycleGroup, 'zmCycle'.$cycleGroup, array( 'cycle', $cycleWidth, $cycleHeight ), translate('Cycle'), $running ) ?>&nbsp;/&nbsp;
        <?php echo makePopupLink( '?view=montage&amp;group='.$cycleGroup, 'zmMontage'.$cycleGroup, 'montage', translate('Montage'), $running ) ?>&nbsp;/&nbsp;
        <?php echo makePopupLink( '?view=montagereview&amp;group='.$cycleGroup, 'zmMontage'.$cycleGroup, 'montagereview', translate('Montage Review'), $running ) ?>
      </div>
<?php
}
?>
      <h3 id="loginBandwidth"><?php
if ( ZM_OPT_USE_AUTH ) {
?><?php echo translate('LoggedInAs') ?> <?php echo makePopupLink( '?view=logout', 'zmLogout', 'logout', $user['Username'], (ZM_AUTH_TYPE == 'builtin') ) ?>, <?php echo strtolower( translate('ConfiguredFor') ) ?><?php
} else {
?><?php echo translate('ConfiguredFor') ?><?php
}
?>&nbsp;<?php echo makePopupLink( '?view=bandwidth', 'zmBandwidth', 'bandwidth', $bwArray[$_COOKIE['zmBandwidth']], ($user && $user['MaxBandwidth'] != 'low' ) ) ?> <?php echo translate('BandwidthHead') ?></h3>
    </div>
    <div id="content">
      <table id="consoleTable">
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
<?php
      for ( $i = 0; $i < count($eventCounts); $i++ ) {
?>
            <th class="colEvents"><?php echo $eventCounts[$i]['title'] ?></th>
<?php
      }
?>
            <th class="colZones"><?php echo translate('Zones') ?></th>
            <th class="colMark"><?php echo translate('Mark') ?></th>
          </tr>
        </thead>
        <tbody id="consoleTableBody">
<?php
foreach( $displayMonitors as $monitor ) {
?>
          <tr id="<?php echo 'monitor_id-'.$monitor['Id'] ?>">
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
<?php if ( ZM_WEB_ID_ON_CONSOLE ) { ?>
            <td class="colId"><?php echo makePopupLink( '?view=watch&amp;mid='.$monitor['Id'], 'zmWatch'.$monitor['Id'], array( 'watch', reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ) ), $monitor['Id'], $running && ($monitor['Function'] != 'None') && canView( 'Stream' ) ) ?></td>
<?php } ?>
            <td class="colName"><?php echo makePopupLink( '?view=watch&amp;mid='.$monitor['Id'], 'zmWatch'.$monitor['Id'], array( 'watch', reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ) ), $monitor['Name'], $running && ($monitor['Function'] != 'None') && canView( 'Stream' ) ) ?></td>
            <td class="colFunction"><?php echo makePopupLink( '?view=function&amp;mid='.$monitor['Id'], 'zmFunction', 'function', '<span class="'.$fclass.'">'.translate('Fn'.$monitor['Function']).( empty($monitor['Enabled']) ? ', disabled' : '' ) .'</span>', canEdit( 'Monitors' ) ) ?></td>
<?php if ( count($servers) ) { ?>
            <td class="colServer"><?php 
$Server = new Server( $monitor['ServerId'] );
echo $Server->Name();
 ?></td>
<?php
    }
    $source = '';
    if ( $monitor['Type'] == 'Local' ) {
      $source = $monitor['Device'].' ('.$monitor['Channel'].')';
    }  elseif ( $monitor['Type'] == 'Remote' ) {
      $source = preg_replace( '/^.*@/', '', $monitor['Host'] );
    } elseif ( $monitor['Type'] == 'File' || $monitor['Type'] == 'cURL' ) {
      $source = preg_replace( '/^.*\//', '', $monitor['Path'] );
    } elseif ( $monitor['Type'] == 'Ffmpeg' || $monitor['Type'] == 'Libvlc' ) {
      $domain = parse_url( $monitor['Path'], PHP_URL_HOST );
      $source = $domain ? $domain : preg_replace( '/^.*\//', '', $monitor['Path'] );
    } elseif ( $monitor['Type'] == 'cURL' ) {
      
    }
    if ( $source == '' ) {
      $source = 'Monitor ' . $monitor['Id'];
    }
    echo '<td class="colSource">'. makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.$source.'</span>', canEdit( 'Monitors' ) ).'</td>';

    for ( $i = 0; $i < count($eventCounts); $i++ ) {
?>
            <td class="colEvents"><?php echo makePopupLink( '?view='.$eventsView.'&amp;page=1'.$monitor['eventCounts'][$i]['filter']['query'], $eventsWindow, $eventsView, $monitor['EventCount'.$i], canView( 'Events' ) ) ?></td>
<?php
    }
?>
            <td class="colZones"><?php echo makePopupLink( '?view=zones&amp;mid='.$monitor['Id'], 'zmZones', array( 'zones', $monitor['Width'], $monitor['Height'] ), $monitor['ZoneCount'], $running && canView( 'Monitors' ) ) ?></td>
            <td class="colMark">
              <input type="checkbox" name="markMids[]" value="<?php echo $monitor['Id'] ?>" onclick="setButtonStates( this )"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/>
              <span class="glyphicon glyphicon-sort"></span>
            </td>
          </tr>
<?php
}
?>
        </tbody>
        <tfoot>
          <tr>
            <td class="colLeftButtons" colspan="<?php echo $left_columns ?>">
              <input type="button" value="<?php echo translate('Refresh') ?>" onclick="location.reload(true);"/>
              <input type="button" name="addBtn" value="<?php echo translate('AddNewMonitor') ?>" onclick="addMonitor( this )"/>
              <!-- <?php echo makePopupButton( '?view=monitor', 'zmMonitor0', 'monitor', translate('AddNewMonitor'), (canEdit( 'Monitors' ) && !$user['MonitorIds']) ) ?> -->
              <?php echo makePopupButton( '?view=filter&filter[terms][0][attr]=DateTime&filter[terms][0][op]=%3c&filter[terms][0][val]=now', 'zmFilter', 'filter', translate('Filters'), canView( 'Events' ) ) ?>
              <input type="button" name="editBtn" value="<?php echo translate('Edit') ?>" onclick="editMonitor( this )" disabled="disabled"/>
              <input type="button" name="deleteBtn" value="<?php echo translate('Delete') ?>" onclick="deleteMonitor( this )" disabled="disabled"/>
            </td>
<?php
for ( $i = 0; $i < count($eventCounts); $i++ ) {
  parseFilter( $eventCounts[$i]['filter'] );
?>
            <td class="colEvents"><?php echo makePopupLink( '?view='.$eventsView.'&amp;page=1'.$eventCounts[$i]['filter']['query'], $eventsWindow, $eventsView, $eventCounts[$i]['total'], canView( 'Events' ) ) ?></td>
<?php
}
?>
            <td class="colZones"><?php echo $zoneCount ?></td>
            <td class="colMark"></td>
          </tr>
        </tfoot>
      </table>
    </div>
    </form>
  </div>
</body>
</html>
