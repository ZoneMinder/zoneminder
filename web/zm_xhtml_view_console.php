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

$running = daemonCheck();
$status = $running?$zmSlangRunning:$zmSlangStopped;

$sql = "select * from Groups where Name = 'Mobile'";
$result = mysql_query( $sql );
if ( !$result )
	echo mysql_error();
$group = mysql_fetch_assoc( $result );
mysql_free_result( $result );

$db_now = strftime( STRF_FMT_DATETIME_DB );
$sql = "select M.*, count(if(E.StartTime>'$db_now' - INTERVAL 1 HOUR && E.Archived = 0,1,NULL)) as HourEventCount, count(if((to_days(E.StartTime)=to_days('$db_now')) && E.Archived = 0,1,NULL)) as TodayEventCount from Monitors as M left join Events as E on E.MonitorId = M.Id group by M.Id order by M.Sequence";
$result = mysql_query( $sql );
if ( !$result )
	echo mysql_error();
$monitors = array();
$max_width = 0;
$max_height = 0;
$cycle_count = 0;
while( $row = mysql_fetch_assoc( $result ) )
{
	if ( !visibleMonitor( $row['Id'] ) )
	{
		continue;
	}
	if ( $group && $group['MonitorIds'] && !in_array( $row['Id'], split( ',', $group['MonitorIds'] ) ) )
	{
		continue;
	}
	$row['zmc'] = zmcStatus( $row );
	$row['zma'] = zmaStatus( $row );
	$sql = "select count(Id) as ZoneCount from Zones where MonitorId = '".$row['Id']."'";
	$result2 = mysql_query( $sql );
	if ( !$result2 )
		echo mysql_error();
	$row2 = mysql_fetch_assoc( $result2 );
	mysql_free_result( $result2 );
	$monitors[] = array_merge( $row, $row2 );
	if ( $row['Function'] != 'None' )
	{
		$cycle_count++;
		if ( $max_width < $row['Width'] ) $max_width = $row['Width'];
		if ( $max_height < $row['Height'] ) $max_height = $row['Height'];
	}
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
$hour_event_count = 0;
$today_event_count = 0;
foreach( $monitors as $monitor )
{
	$hour_event_count += $monitor['HourEventCount'];
	$today_event_count += $monitor['TodayEventCount'];
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
<td align="right" style="width: 3em"><?= makeLink( "$PHP_SELF?view=events&amp;page=1&amp;filter=1&amp;trms=3&amp;attr1=MonitorId&amp;op1=%3d&amp;val1=".$monitor['Id']."&amp;cnj2=and&amp;attr2=Archived&amp;op2=%3d&amp;val2=0&amp;cnj3=and&amp;attr3=DateTime&amp;op3=%3e%3d&amp;val3=-1%20hour", $monitor['HourEventCount'], canView( 'Events' ) ) ?></td>
<td align="right" style="width: 3em"><?= makeLink( "$PHP_SELF?view=events&amp;page=1&amp;filter=1&amp;trms=3&amp;attr1=MonitorId&amp;op1=%3d&amp;val1=".$monitor['Id']."&amp;cnj2=and&amp;attr2=Archived&amp;op2=%3d&amp;val2=0&amp;cnj3=and&amp;attr3=Date&amp;op3=%3e%3d&amp;val3=today", $monitor['TodayEventCount'], canView( 'Events' ) ) ?></td>
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
<td align="right"><?= makeLink( "$PHP_SELF?view=events&amp;page=1&amp;filter=1&amp;trms=2&amp;attr1=Archived&amp;op1=%3d&amp;val1=0&amp;cnj2=and&amp;attr2=DateTime&amp;op2=%3e%3d&amp;val2=-1%20hour", $hour_event_count, canView( 'Events' ) ) ?></td>
<td align="right"><?= makeLink( "$PHP_SELF?view=events&amp;page=1&amp;filter=1&amp;trms=2&amp;attr1=Archived&amp;op1=%3d&amp;val1=0&amp;cnj2=and&amp;attr2=Date&amp;op2=%3e%3d&amp;val2=today", $today_event_count, canView( 'Events' ) ) ?></td>
</tr>
</table>
</body>
</html>
