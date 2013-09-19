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
    // Last Hour
    array(
        "filter" => array(
            "terms" => array(
                array( "attr" => "Archived", "op" => "=", "val" => "0" ),
                array( "cnj" => "and", "attr" => "DateTime", "op" => ">=", "val" => "-1 hour" ),
            )
        ),
    ),
    // Today
    array(
        "filter" => array(
            "terms" => array(
                array( "attr" => "Archived", "op" => "=", "val" => "0" ),
                array( "cnj" => "and", "attr" => "DateTime", "op" => ">=", "val" => "today" ),
            )
        ),
    ),
);

$running = daemonCheck();
$status = $running?$SLANG['Running']:$SLANG['Stopped'];

if ( $group = dbFetchOne( "select * from Groups where Name = 'Mobile'" ) )
    $groupIds = array_flip(explode( ',', $group['MonitorIds'] ));

$maxWidth = 0;
$maxHeight = 0;
$cycleCount = 0;
$monitors = dbFetchAll( "select * from Monitors order by Sequence asc" );
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
    $monitors[$i]['zmc'] = zmcStatus( $monitors[$i] );
    $monitors[$i]['zma'] = zmaStatus( $monitors[$i] );
    $counts = array();
    for ( $j = 0; $j < count($eventCounts); $j++ )
    {
        $filter = addFilterTerm( $eventCounts[$j]['filter'], count($eventCounts[$j]['filter']['terms']), array( "cnj" => "and", "attr" => "MonitorId", "op" => "=", "val" => $monitors[$i]['Id'] ) );
        parseFilter( $filter, false, '&amp;' );
        $counts[] = "count(if(1".$filter['sql'].",1,NULL)) as EventCount$j";
        $monitors[$i]['eventCounts'][$j]['filter'] = $filter;
    }
    $sql = "select ".join($counts,", ")." from Events as E where MonitorId = '".$monitors[$i]['Id']."'";
    $counts = dbFetchOne( $sql );
    if ( $monitors[$i]['Function'] != 'None' )
    {
        $cycleCount++;
        if ( $maxWidth < $monitors[$i]['Width'] ) $maxWidth = $monitors[$i]['Width'];
        if ( $maxHeight < $monitors[$i]['Height'] ) $maxHeight = $monitors[$i]['Height'];
    }
    $monitors[$i] = array_merge( $monitors[$i], $counts );
}

xhtmlHeaders( __FILE__, $SLANG['Console'] );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="systemTime"><a href="?view=<?= $view ?>"><?= preg_match( '/%/', DATE_FMT_CONSOLE_SHORT )?strftime( DATE_FMT_CONSOLE_SHORT ):date( DATE_FMT_CONSOLE_SHORT ) ?></a></div>
      <div id="systemStats"><?= getLoad() ?>/<?= getDiskPercent() ?>%</div>
      <div id="systemState"><?= makeLink( "?view=state", $status, canEdit( 'System' ) ) ?></div>
    </div>
    <div id="content">
      <table id="contentTable">
<?php
for ( $i = 0; $i < count($eventCounts); $i++ )
{
    $eventCounts[$i]['total'] = 0;
}
$zoneCount = 0;
foreach( $monitors as $monitor )
{
    if ( empty($monitor['Show']) )
        continue;
    for ( $i = 0; $i < count($eventCounts); $i++ )
    {
        $eventCounts[$i]['total'] += $monitor['EventCount'.$i];
    }
    //$zoneCount += $monitor['ZoneCount'];
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
?>
          <td class="colName"><?= makeLink( "?view=watch&amp;mid=".$monitor['Id'], substr( $monitor['Name'], 0, 8 ), $running && ($monitor['Function'] != 'None') && canView( 'Stream' ) ) ?></td>
          <td class="colFunction"><?= makeLink( "?view=function&amp;mid=".$monitor['Id'], "<span class=\"$fclass\">".substr( $monitor['Function'], 0, 4 )."</span>", canEdit( 'Monitors' ) ) ?></td>
<?php
for ( $i = 0; $i < count($eventCounts); $i++ )
{
?>
          <td class="colEvents"><?= makeLink( "?view=events&amp;page=1".$monitor['eventCounts'][$i]['filter']['query'], $monitor['EventCount'.$i], canView( 'Events' ) ) ?></td>
<?php
}
?>
        </tr>
<?php
}
?>
        <tr>
<?php
if ( ZM_OPT_X10 ) {
?>
          <td><?= makeLink( "?view=devices", $SLANG['Devices'], canView('Devices' ) ) ?></td>
<?php
} else {
?>
          <td>&nbsp;</td>
<?php
}
if ( $cycleCount > 1 ) {
?>
          <td><?= makeLink( "?view=montage", $SLANG['Montage'], $running && canView( 'Stream' ) ) ?></td>
<?php
} else {
?>
          <td>&nbsp;</td>
<?php
}
for ( $i = 0; $i < count($eventCounts); $i++ )
{
    parseFilter( $eventCounts[$i]['filter'], false, '&amp;' );
?>
          <td class="colEvents"><?= makeLink( "?view=events&amp;page=1".$eventCounts[$i]['filter']['query'], $eventCounts[$i]['total'], canView( 'Events' ) ) ?></td>
<?php
}
?>
        </tr>
      </table>
    </div>
  </div>
</body>
</html>
