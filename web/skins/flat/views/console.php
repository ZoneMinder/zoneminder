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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

$eventCounts = array(
    array(
        "title" => $SLANG['Events'],
        "filter" => array(
            "terms" => array(
            )
        ),
    ),
    array(
        "title" => $SLANG['Hour'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "DateTime", "op" => ">=", "val" => "-1 hour" ),
            )
        ),
    ),
    array(
        "title" => $SLANG['Day'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "DateTime", "op" => ">=", "val" => "-1 day" ),
            )
        ),
    ),
    array(
        "title" => $SLANG['Week'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "DateTime", "op" => ">=", "val" => "-7 day" ),
            )
        ),
    ),
    array(
        "title" => $SLANG['Month'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "DateTime", "op" => ">=", "val" => "-1 month" ),
            )
        ),
    ),
    array(
        "title" => $SLANG['Archived'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "Archived", "op" => "=", "val" => "1" ),
            )
        ),
    ),
);

$running = daemonCheck();
$status = $running?$SLANG['Running']:$SLANG['Stopped'];

$group = NULL;
if ( ! empty($_COOKIE['zmGroup']) ) {
	if ( $group = dbFetchOne( 'SELECT * FROM Groups WHERE Id = ?', NULL, array($_COOKIE['zmGroup']) ) )
		$groupIds = array_flip(explode( ',', $group['MonitorIds'] ));
}

noCacheHeaders();

$maxWidth = 0;
$maxHeight = 0;
$cycleCount = 0;
$minSequence = 0;
$maxSequence = 1;
$seqIdList = array();
$monitors = dbFetchAll( 'SELECT * FROM Monitors ORDER BY Sequence ASC' );
$displayMonitors = array();
for ( $i = 0; $i < count($monitors); $i++ )
{
    if ( !visibleMonitor( $monitors[$i]['Id'] ) )
    {
        continue;
    }
    if ( $group && !empty($groupIds) && !array_key_exists( $monitors[$i]['Id'], $groupIds ) )
    {
        continue;
    }
    $monitors[$i]['Show'] = true;
    if ( empty($minSequence) || ($monitors[$i]['Sequence'] < $minSequence) )
    {
        $minSequence = $monitors[$i]['Sequence'];
    }
    if ( $monitors[$i]['Sequence'] > $maxSequence )
    {
        $maxSequence = $monitors[$i]['Sequence'];
    }
    $monitors[$i]['zmc'] = zmcStatus( $monitors[$i] );
    $monitors[$i]['zma'] = zmaStatus( $monitors[$i] );
    $monitors[$i]['ZoneCount'] = dbFetchOne( 'SELECT count(Id) AS ZoneCount FROM Zones WHERE MonitorId = ?', 'ZoneCount', array( $monitors[$i]['Id'] ) );
    $counts = array();
    for ( $j = 0; $j < count($eventCounts); $j++ )
    {
        $filter = addFilterTerm( $eventCounts[$j]['filter'], count($eventCounts[$j]['filter']['terms']), array( "cnj" => "and", "attr" => "MonitorId", "op" => "=", "val" => $monitors[$i]['Id'] ) );
        parseFilter( $filter );
        $counts[] = "count(if(1".$filter['sql'].",1,NULL)) as EventCount$j";
        $monitors[$i]['eventCounts'][$j]['filter'] = $filter;
    }
    $sql = 'SELECT '.join($counts,', ').' FROM Events AS E WHERE MonitorId = ?';
    $counts = dbFetchOne( $sql, NULL, array( $monitors[$i]['Id'] ) );
    if ( $monitors[$i]['Function'] != 'None' )
    {
        $cycleCount++;
        $scaleWidth = reScale( $monitors[$i]['Width'], $monitors[$i]['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
        $scaleHeight = reScale( $monitors[$i]['Height'], $monitors[$i]['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
        if ( $maxWidth < $scaleWidth ) $maxWidth = $scaleWidth;
        if ( $maxHeight < $scaleHeight ) $maxHeight = $scaleHeight;
    }
    if ( $counts ) $monitors[$i] = array_merge( $monitors[$i], $counts );
    $seqIdList[] = $monitors[$i]['Id'];
    $displayMonitors[] = $monitors[$i];
}
$lastId = 0;
$seqIdUpList = array();
foreach ( $seqIdList as $seqId )
{
    if ( !empty($lastId) )
        $seqIdUpList[$seqId] = $lastId;
    else
        $seqIdUpList[$seqId] = $seqId;
    $lastId = $seqId;
}
$lastId = 0;
$seqIdDownList = array();
foreach ( array_reverse($seqIdList) as $seqId )
{
    if ( !empty($lastId) )
        $seqIdDownList[$seqId] = $lastId;
    else
        $seqIdDownList[$seqId] = $seqId;
    $lastId = $seqId;
}

$cycleWidth = $maxWidth;
$cycleHeight = $maxHeight;

$eventsView = ZM_WEB_EVENTS_VIEW;
$eventsWindow = 'zm'.ucfirst(ZM_WEB_EVENTS_VIEW);

$eventCount = 0;
for ( $i = 0; $i < count($eventCounts); $i++ )
{
    $eventCounts[$i]['total'] = 0;
}
$zoneCount = 0;
foreach( $displayMonitors as $monitor )
{
    for ( $i = 0; $i < count($eventCounts); $i++ )
    {
        $eventCounts[$i]['total'] += $monitor['EventCount'.$i];
    }
    $zoneCount += $monitor['ZoneCount'];
}

$seqUpFile = getSkinFile( 'graphics/seq-u.gif' );
$seqDownFile = getSkinFile( 'graphics/seq-d.gif' );

$versionClass = (ZM_DYN_DB_VERSION&&(ZM_DYN_DB_VERSION!=ZM_VERSION))?'errorText':'';

xhtmlHeaders( __FILE__, $SLANG['Console'] );
?>
<body>
  <div id="page">
    <form name="monitorForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
    <input type="hidden" name="view" value="<?php echo $view ?>"/>
    <input type="hidden" name="action" value=""/>
    <div id="header">
      <h3 id="systemTime"><?php echo preg_match( '/%/', DATE_FMT_CONSOLE_LONG )?strftime( DATE_FMT_CONSOLE_LONG ):date( DATE_FMT_CONSOLE_LONG ) ?></h3>
      <h3 id="systemStats"><?php echo $SLANG['Load'] ?>: <?php echo getLoad() ?> / <?php echo $SLANG['Disk'] ?>: <?php echo getDiskPercent() ?>%</h3>
      <h2 id="title"><a href="http://www.zoneminder.com" target="ZoneMinder">ZoneMinder</a> <?php echo $SLANG['Console'] ?> - <?php echo makePopupLink( '?view=state', 'zmState', 'state', $status, canEdit( 'System' ) ) ?> - <?php echo makePopupLink( '?view=version', 'zmVersion', 'version', '<span class="'.$versionClass.'">v'.ZM_VERSION.'</span>', canEdit( 'System' ) ) ?></h2>
      <div class="clear"></div>
      <div id="monitorSummary"><?php echo makePopupLink( '?view=groups', 'zmGroups', 'groups', sprintf( $CLANG['MonitorCount'], count($displayMonitors), zmVlang( $VLANG['Monitor'], count($displayMonitors) ) ).($group?' ('.$group['Name'].')':''), canView( 'System' ) ); ?></div>
<?php
if ( ZM_OPT_X10 && canView( 'Devices' ) )
{
?>
      <div id="devices"><?php echo makePopupLink( '?view=devices', 'zmDevices', 'devices', $SLANG['Devices'] ) ?></div>
<?php
}
if ( canView( 'System' ) )
{
?>
      <div id="options"><?php echo makePopupLink( '?view=options', 'zmOptions', 'options', $SLANG['Options'] ) ?><?php if ( logToDatabase() > Logger::NOLOG ) { ?> / <?php echo makePopupLink( '?view=log', 'zmLog', 'log', '<span class="'.logState().'">'.$SLANG['Log'].'</span>' ) ?><?php } ?></div>
<?php
}
if ( canView( 'Stream' ) && $cycleCount > 1 )
{
    $cycleGroup = isset($_COOKIE['zmGroup'])?$_COOKIE['zmGroup']:0;
?>
      <div id="cycleMontage"><?php echo makePopupLink( '?view=cycle&amp;group='.$cycleGroup, 'zmCycle'.$cycleGroup, array( 'cycle', $cycleWidth, $cycleHeight ), $SLANG['Cycle'], $running ) ?>&nbsp;/&nbsp;<?php echo makePopupLink( '?view=montage&amp;group='.$cycleGroup, 'zmMontage'.$cycleGroup, 'montage', $SLANG['Montage'], $running ) ?></div>
<?php
}
else
{
?>
<?php
}
?>
      <h3 id="loginBandwidth"><?php
if ( ZM_OPT_USE_AUTH )
{
?><?php echo $SLANG['LoggedInAs'] ?> <?php echo makePopupLink( '?view=logout', 'zmLogout', 'logout', $user['Username'], (ZM_AUTH_TYPE == "builtin") ) ?>, <?php echo strtolower( $SLANG['ConfiguredFor'] ) ?><?php
}
else
{
?><?php echo $SLANG['ConfiguredFor'] ?><?php
}
?>&nbsp;<?php echo makePopupLink( '?view=bandwidth', 'zmBandwidth', 'bandwidth', $bwArray[$_COOKIE['zmBandwidth']], ($user && $user['MaxBandwidth'] != 'low' ) ) ?> <?php echo $SLANG['Bandwidth'] ?></h3>
    </div>
    <div id="content">
      <table id="consoleTable" cellspacing="0">
        <thead>
          <tr>
            <th class="colName"><?php echo $SLANG['Name'] ?></th>
            <th class="colFunction"><?php echo $SLANG['Function'] ?></th>
            <th class="colSource"><?php echo $SLANG['Source'] ?></th>
<?php
for ( $i = 0; $i < count($eventCounts); $i++ )
{
?>
            <th class="colEvents"><?php echo $eventCounts[$i]['title'] ?></th>
<?php
}
?>
            <th class="colZones"><?php echo $SLANG['Zones'] ?></th>
<?php
if ( canEdit('Monitors') )
{
?>
            <th class="colOrder"><?php echo $SLANG['Order'] ?></th>
<?php
}
?>
            <th class="colMark"><?php echo $SLANG['Mark'] ?></th>
          </tr>
        </thead>
        <tfoot>
          <tr>
            <td class="colLeftButtons" colspan="3">
              <input type="button" value="<?php echo $SLANG['Refresh'] ?>" onclick="location.reload(true);"/>
              <?php echo makePopupButton( '?view=monitor', 'zmMonitor0', 'monitor', $SLANG['AddNewMonitor'], (canEdit( 'Monitors' ) && !$user['MonitorIds']) ) ?>
              <?php echo makePopupButton( '?view=filter&amp;filter[terms][0][attr]=DateTime&amp;filter[terms][0][op]=%3c&amp;filter[terms][0][val]=now', 'zmFilter', 'filter', $SLANG['Filters'], canView( 'Events' ) ) ?>
            </td>
<?php
for ( $i = 0; $i < count($eventCounts); $i++ )
{
    parseFilter( $eventCounts[$i]['filter'] );
?>
            <td class="colEvents"><?php echo makePopupLink( '?view='.$eventsView.'&amp;page=1'.$eventCounts[$i]['filter']['query'], $eventsWindow, $eventsView, $eventCounts[$i]['total'], canView( 'Events' ) ) ?></td>
<?php
}
?>
            <td class="colZones"><?php echo $zoneCount ?></td>
            <td class="colRightButtons" colspan="<?php echo canEdit('Monitors')?2:1 ?>"><input type="button" name="editBtn" value="<?php echo $SLANG['Edit'] ?>" onclick="editMonitor( this )" disabled="disabled"/><input type="button" name="deleteBtn" value="<?php echo $SLANG['Delete'] ?>" onclick="deleteMonitor( this )" disabled="disabled"/></td>
          </tr>
        </tfoot>
        <tbody>
<?php
foreach( $displayMonitors as $monitor )
{
?>
          <tr>
<?php
    if ( !$monitor['zmc'] )
        $dclass = "errorText";
    else
    {
        if ( !$monitor['zma'] )
            $dclass = "warnText";
        else
            $dclass = "infoText";
    }
    if ( $monitor['Function'] == 'None' )
        $fclass = "errorText";
    elseif ( $monitor['Function'] == 'Monitor' )
        $fclass = "warnText";
    else
        $fclass = "infoText";
    if ( !$monitor['Enabled'] )
        $fclass .= " disabledText";
    $scale = max( reScale( SCALE_BASE, $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );
?>
            <td class="colName"><?php echo makePopupLink( '?view=watch&amp;mid='.$monitor['Id'], 'zmWatch'.$monitor['Id'], array( 'watch', reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ) ), $monitor['Name'], $running && ($monitor['Function'] != 'None') && canView( 'Stream' ) ) ?></td>
            <td class="colFunction"><?php echo makePopupLink( '?view=function&amp;mid='.$monitor['Id'], 'zmFunction', 'function', '<span class="'.$fclass.'">'.$monitor['Function'].'</span>', canEdit( 'Monitors' ) ) ?></td>
<?php if ( $monitor['Type'] == "Local" ) { ?>
            <td class="colSource"><?php echo makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.$monitor['Device'].' ('.$monitor['Channel'].')</span>', canEdit( 'Monitors' ) ) ?></td>
<?php } elseif ( $monitor['Type'] == "Remote" ) { ?>
            <td class="colSource"><?php echo makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.preg_replace( '/^.*@/', '', $monitor['Host'] ).'</span>', canEdit( 'Monitors' ) ) ?></td>
<?php } elseif ( $monitor['Type'] == "File" ) { ?>
            <td class="colSource"><?php echo makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.preg_replace( '/^.*\//', '', $monitor['Path'] ).'</span>', canEdit( 'Monitors' ) ) ?></td>
<?php } elseif ( $monitor['Type'] == "Ffmpeg" ) { ?>
            <td class="colSource"><?php echo makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.preg_replace( '/^.*\//', '', $monitor['Path'] ).'</span>', canEdit( 'Monitors' ) ) ?></td>
<?php } elseif ( $monitor['Type'] == "Libvlc" ) {
    $domain = parse_url( $monitor['Path'], PHP_URL_HOST );
    $shortpath = $domain ? $domain : preg_replace( '/^.*\//', '', $monitor['Path'] );
?>
            <td class="colSource"><?php echo makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.$shortpath.'</span>', canEdit( 'Monitors' ) ) ?></td>
<?php } elseif ( $monitor['Type'] == "cURL" ) { ?>
            <td class="colSource"><?php echo makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.preg_replace( '/^.*\//', '', $monitor['Path'] ).'</span>', canEdit( 'Monitors' ) ) ?></td>
<?php } else { ?>
            <td class="colSource">&nbsp;</td>
<?php } ?>
<?php
    for ( $i = 0; $i < count($eventCounts); $i++ )
    {
?>
            <td class="colEvents"><?php echo makePopupLink( '?view='.$eventsView.'&amp;page=1'.$monitor['eventCounts'][$i]['filter']['query'], $eventsWindow, $eventsView, $monitor['EventCount'.$i], canView( 'Events' ) ) ?></td>
<?php
    }
?>
            <td class="colZones"><?php echo makePopupLink( '?view=zones&amp;mid='.$monitor['Id'], 'zmZones', array( 'zones', $monitor['Width'], $monitor['Height'] ), $monitor['ZoneCount'], canView( 'Monitors' ) ) ?></td>
<?php
    if ( canEdit('Monitors') )
    {
?>
            <td class="colOrder"><?php echo makeLink( '?view='.$view.'&amp;action=sequence&amp;mid='.$monitor['Id'].'&amp;smid='.$seqIdUpList[$monitor['Id']], '<img src="'.$seqUpFile.'" alt="Up"/>', $monitor['Sequence']>$minSequence ) ?><?php echo makeLink( '?view='.$view.'&amp;action=sequence&amp;mid='.$monitor['Id'].'&amp;smid='.$seqIdDownList[$monitor['Id']], '<img src="'.$seqDownFile.'" alt="Down"/>', $monitor['Sequence']<$maxSequence ) ?></td>
<?php
    }
?>
            <td class="colMark"><input type="checkbox" name="markMids[]" value="<?php echo $monitor['Id'] ?>" onclick="setButtonStates( this )"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/></td>
          </tr>
<?php
}
?>
        </tbody>
      </table>
    </div>
    </form>
  </div>
</body>
</html>
