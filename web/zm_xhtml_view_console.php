<?php
//
// ZoneMinder web console file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

$event_counts = array(
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
$status = $running?$zmSlangRunning:$zmSlangStopped;

$sql = "select * from Groups where Name = 'Mobile'";
$result = mysql_query( $sql );
if ( !$result )
	echo mysql_error();
if ( $group = dbFetchOne( "select * from Groups where Id = '$cgroup'" ) )
    $group_ids = array_flip(split( ',', $group['MonitorIds'] ));

$db_now = strftime( STRF_FMT_DATETIME_DB );
$monitors = array();
$max_width = 0;
$max_height = 0;
$cycle_count = 0;
$monitors = dbFetchAll( "select * from Monitors order by Sequence asc" );
for ( $i = 0; $i < count($monitors); $i++ )
{
	if ( !visibleMonitor( $monitors[$i]['Id'] ) )
	{
		continue;
	}
    if ( $group && !empty($group_ids) && !array_key_exists( $monitors[$i]['Id'], $group_ids ) )
	{
		continue;
	}
    $monitors[$i]['Show'] = true;
	$monitors[$i]['zmc'] = zmcStatus( $monitors[$i] );
	$monitors[$i]['zma'] = zmaStatus( $monitors[$i] );
    //$monitors[$i]['ZoneCount'] = dbFetchOne( "select count(Id) as ZoneCount from Zones where MonitorId = '".$monitors[$i]['Id']."'", "ZoneCount" );
    $counts = array();
    for ( $j = 0; $j < count($event_counts); $j++ )
    {
        $filter = addFilterTerm( $event_counts[$j]['filter'], count($event_counts[$j]['filter']['terms']), array( "cnj" => "and", "attr" => "MonitorId", "op" => "=", "val" => $monitors[$i]['Id'] ) );
        parseFilter( $filter, false, '&amp;' );
        $counts[] = "count(if(1".$filter['sql'].",1,NULL)) as EventCount$j";
        $monitors[$i]['event_counts'][$j]['filter'] = $filter;
    }
    $sql = "select ".join($counts,", ")." from Events as E where MonitorId = '".$monitors[$i]['Id']."'";
    $counts = dbFetchOne( $sql );
	if ( $monitors[$i]['Function'] != 'None' )
	{
		$cycle_count++;
		if ( $max_width < $monitors[$i]['Width'] ) $max_width = $monitors[$i]['Width'];
		if ( $max_height < $monitors[$i]['Height'] ) $max_height = $monitors[$i]['Height'];
	}
    $monitors[$i] = array_merge( $monitors[$i], $counts );
}
mysql_free_result( $result );
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangConsole ?></title>
<link rel="stylesheet" href="zm_xhtml_styles.css" type="text/css"/>
</head>
<body>
<table style="width: 100%">
<tr>
<td align="left"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>"><?= preg_match( '/%/', DATE_FMT_CONSOLE_SHORT )?strftime( DATE_FMT_CONSOLE_SHORT ):date( DATE_FMT_CONSOLE_SHORT ) ?></a></td><td align="center"><?= makeLink( "$PHP_SELF?view=state", $status, canEdit( 'System' ) ) ?></td><td align="right"><?= getLoad() ?>/<?= getDiskPercent() ?>%</td>
</tr>
</table>
<table style="width: 100%">
<?php
for ( $i = 0; $i < count($event_counts); $i++ )
{
    $event_counts[$i]['total'] = 0;
}
$zone_count = 0;
foreach( $monitors as $monitor )
{
    if ( empty($monitor['Show']) )
        continue;
    for ( $i = 0; $i < count($event_counts); $i++ )
    {
	    $event_counts[$i]['total'] += $monitor['EventCount'.$i];
    }
	//$zone_count += $monitor['ZoneCount'];
?>
<tr>
<?php
	if ( !$monitor['zmc'] )
	{
		$dclass = "redtext";
	}
	else
	{
		if ( !$monitor['zma'] )
		{
			$dclass = "ambtext";
		}
		else
		{
			$dclass = "gretext";
		}
	}
	if ( $monitor['Function'] == 'None' )
	{
		$fclass = "redtext";
	}
	elseif ( $monitor['Function'] == 'Monitor' )
	{
		$fclass = "ambtext";
	}
	else
	{
		$fclass = "gretext";
	}
	if ( !$monitor['Enabled'] )
	{
		$fclass .= "em";
	}
?>
<td align="left" style="width: 6em"><?= makeLink( "$PHP_SELF?view=watch&amp;mid=".$monitor['Id'], substr( $monitor['Name'], 0, 8 ), $running && ($monitor['Function'] != 'None') && canView( 'Stream' ) ) ?></td>
<td align="left" style="width: 4em"><?= makeLink( "$PHP_SELF?view=function&amp;mid=".$monitor['Id'], "<span class=\"$fclass\">".substr( $monitor['Function'], 0, 4 )."</span>", canEdit( 'Monitors' ) ) ?></td>
<?php
for ( $i = 0; $i < count($event_counts); $i++ )
{
?>
<td align="right" style="width: 3em"><?= makeLink( "$PHP_SELF?view=events&amp;page=1&amp;".$monitor['event_counts'][$i]['filter']['query'], $monitor['EventCount'.$i], canView( 'Events' ) ) ?></td>
<?php
}
?>
</tr>
<?php
}
?>
<tr>
<?php
if ( ZM_OPT_X10 )
{
?>
<td align="left"><?= makeLink( "$PHP_SELF?view=devices", $zmSlangDevices, canView('Devices' ) ) ?></td>
<?php
}
else
{
?>
<td align="left">&nbsp;</td>
<?php
}
?>
<td align="center"><?= makeLink( "$PHP_SELF?view=montage", count($monitors), ( $running && canView( 'Stream' ) && $cycle_count > 1 ) ) ?></td>
<?php
for ( $i = 0; $i < count($event_counts); $i++ )
{
    parseFilter( $event_counts[$i]['filter'], false, '&amp;' );
?>
<td align="right"><?= makeLink( "$PHP_SELF?view=events&amp;page=1&amp;".$event_counts[$i]['filter']['query'], $event_counts[$i]['total', canView( 'Events' ) ) ?></td>
<?php
}
?>
</tr>
</table>
</body>
</html>
