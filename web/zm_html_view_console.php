<?php
//
// ZoneMinder web console file, $Date$, $Revision$
// Copyright (C) 2003  Philip Coombes
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

if ( !isset($cgroup) )
{
	$cgroup = 0;
}
$sql = "select * from Groups where Id = '$cgroup'";
$result = mysql_query( $sql );
if ( !$result )
	echo mysql_error();
$group = mysql_fetch_assoc( $result );

if ( ZM_WEB_REFRESH_METHOD == "http" )
	header("Refresh: ".ZM_WEB_REFRESH_MAIN."; URL=$PHP_SELF" );
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");			// HTTP/1.0

$db_now = strftime( "%Y-%m-%d %H:%M:%S" );
$sql = "select * from Monitors order by Id";
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
	$row['zmc'] = zmcCheck( $row );
	$row['zma'] = zmaCheck( $row );
	$sql = "select count(Id) as ZoneCount from Zones where MonitorId = '".$row['Id']."'";
	$result2 = mysql_query( $sql );
	if ( !$result2 )
		echo mysql_error();
	$row2 = mysql_fetch_assoc( $result2 );
	$sql = "select count(if(Archived=0,1,NULL)) as EventCount, count(if(Archived,1,NULL)) as ArchEventCount, count(if(StartTime>'$db_now' - INTERVAL 1 HOUR && Archived = 0,1,NULL)) as HourEventCount, count(if(StartTime>'$db_now' - INTERVAL 1 DAY && Archived = 0,1,NULL)) as DayEventCount, count(if(StartTime>'$db_now' - INTERVAL 7 DAY && Archived = 0,1,NULL)) as WeekEventCount, count(if(StartTime>'$db_now' - INTERVAL 1 MONTH && Archived = 0,1,NULL)) as MonthEventCount from Events as E where MonitorId = '".$row['Id']."'";
	$result3 = mysql_query( $sql );
	if ( !$result3 )
		echo mysql_error();
	$row3 = mysql_fetch_assoc( $result3 );
	if ( $row['Function'] != 'None' )
	{
		$cycle_count++;
		if ( $max_width < $row['Width'] ) $max_width = $row['Width'];
		if ( $max_height < $row['Height'] ) $max_height = $row['Height'];
	}
	$monitors[] = $row = array_merge( $row, $row2, $row3 );
}
$montage_rows = intval(ceil($cycle_count/ZM_WEB_MONTAGE_MAX_COLS));
$montage_cols = $cycle_count>=ZM_WEB_MONTAGE_MAX_COLS?ZM_WEB_MONTAGE_MAX_COLS:$cycle_count;
$montage_width = ZM_WEB_MONTAGE_WIDTH?ZM_WEB_MONTAGE_WIDTH:$max_width;
$montage_height = ZM_WEB_MONTAGE_HEIGHT?ZM_WEB_MONTAGE_HEIGHT:$max_height;

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>ZM - Console</title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<link rel="shortcut icon" href="favicon.ico">
<link rel="icon" type="image/ico" href="favicon.ico">
<script language="JavaScript">
window.resizeTo( <?= $jws['console']['w'] ?>, <?= $jws['console']['h'] ?> );
function newWindow(Url,Name,Width,Height)
{
	var Name = window.open(Url,Name,"resizable,width="+Width+",height="+Height);
}
function scrollWindow(Url,Name,Width,Height)
{
	var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function configureButton(form,name)
{
	var checked = false;
	for (var i = 0; i < form.elements.length; i++)
	{
		if ( form.elements[i].name.indexOf(name) == 0)
		{
			if ( form.elements[i].checked )
			{
				checked = true;
				break;
			}
		}
	}
	form.delete_btn.disabled = !checked;
}
function confirmDelete()
{
	return( confirm( 'Warning, deleting a monitor also deletes all events and database entries associated with it.\nAre you sure you wish to delete?' ) );
}
<?php
if ( ZM_WEB_REFRESH_METHOD == "javascript" )
{
?>
//window.setTimeout( "window.location.replace('<?= $PHP_SELF ?>?view=<?= $view ?>')", <?= (ZM_WEB_REFRESH_MAIN*1000) ?> );
window.setTimeout( "window.location.replace('<?= $PHP_SELF ?>')", <?= (ZM_WEB_REFRESH_MAIN*1000) ?> );
<?php
}
?>
<?php
if ( ZM_CHECK_FOR_UPDATES && canEdit( 'System' ) && ZM_DYN_LAST_VERSION && ( ZM_VERSION != ZM_DYN_LAST_VERSION ) && ( ZM_DYN_LAST_VERSION != ZM_DYN_CURR_VERSION ) && ( ZM_DYN_NEXT_REMINDER < time() ) )
{
?>
newWindow( '<?= $PHP_SELF ?>?view=version', 'zmVersion', <?= $jws['version']['w'] ?>, <?= $jws['version']['h'] ?> );
<?php
}
?>
</script>
</head>
<body scroll="auto">
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<form name="monitor_form" method="get" action="<?= $PHP_SELF ?>" onSubmit="return(confirmDelete());">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="delete">
<tr>
<td class="smallhead" align="left"><?= date( "D jS M, g:ia" ) ?></td>
<td class="bighead" align="center"><strong><a href="http://www.zoneminder.com" target="ZoneMinder">ZoneMinder</a> <?= $zmSlangConsole ?> - <?php if ( canEdit( 'System' ) ) { ?><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=state', 'zmState', <?= $jws['state']['w'] ?>, <?= $jws['state']['h'] ?> );"><?= $status ?></a> - <?php } ?><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=version', 'zmVersion', ".$jws['version']['w'].", ".$jws['version']['h']." );", "v".ZM_VERSION, canEdit( 'System' ) ) ?></strong></td>
<td class="smallhead" align="right"><?= $zmSlangLoad ?>: <?= getLoad() ?> / <?= $zmSlangDisk ?>: <?= getDiskPercent() ?>%</td>
</tr>
<tr>
<td class="smallhead" align="left">
<?php
if ( canView( 'System' ) )
{
?>
<a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=groups', 'zmGroups', <?= $jws['groups']['w'] ?>, <?= $jws['groups']['h'] ?> );"><?= sprintf( $zmClangMonitorCount, count($monitors), zmVlang( $zmVlangMonitor, count($monitors) ) ).($group?' ('.$group['Name'].')':'') ?></a>
<?php
}
else
{
?>
<?= sprintf( $zmClangMonitorCount, count($monitors), zmVlang( $zmVlangMonitor, count($monitors) ) ) ?>
<?php
}
?>
</td>
<?php
if ( ZM_OPT_USE_AUTH )
{
?>
<td class="smallhead" align="center"><?= $zmSlangLoggedInAs ?> <a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=logout', 'zmLogout', <?= $jws['logout']['w'] ?>, <?= $jws['logout']['h'] ?>);"><?= $user['Username'] ?></a>, <?= strtolower( $zmSlangConfiguredFor ) ?>
<?php
}
else
{
?>
<td class="smallhead" align="center"><?= $zmSlangConfiguredFor ?>
<?php
}
?>
&nbsp;<a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=bandwidth', 'zmBandwidth', <?= $jws['bandwidth']['w'] ?>, <?= $jws['bandwidth']['h'] ?>);"><?= strtolower( $bw_array[$bandwidth] ) ?></a> <?= strtolower( $zmSlangBandwidth ) ?></td>
<td class="smallhead" align="right"><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td class="smallhead" align="left">
<?php
if ( canView( 'Stream' ) && $cycle_count > 1 )
{
?>
<a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=cycle&group=<?= $cgroup ?>', 'zmCycle<?= $cgroup ?>', <?= $montage_width+$jws['cycle']['w'] ?>, <?= $montage_height+$jws['cycle']['h'] ?> );"><?= $zmSlangCycle ?></a>&nbsp;/&nbsp;<a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=montage&group=<?= $cgroup ?>', 'zmMontage<?= $cgroup ?>', <?= ($montage_cols*$montage_width)+$jws['montage']['w'] ?>, <?= ($montage_rows*((ZM_WEB_COMPACT_MONTAGE?4:40)+$montage_height))+$jws['montage']['h'] ?> );"><?= $zmSlangMontage ?></a>
<?php
}
else
{
?>
&nbsp;
<?php
}
?>
</td><td align="right" class="smallhead"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=options', 'zmOptions', ".$jws['options']['w'].", ".$jws['options']['h']." );", $zmSlangOptions, canView( 'System' ) ) ?></td></tr></table></td>
</tr>
</table>
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<tr><td align="left" class="smallhead"><?= $zmSlangId ?></td>
<td align="left" class="smallhead"><?= $zmSlangName ?></td>
<td align="left" class="smallhead"><?= $zmSlangFunction ?></td>
<td align="left" class="smallhead"><?= $zmSlangSource ?></td>
<td align="right" class="smallhead"><?= $zmSlangEvents ?></td>
<td align="right" class="smallhead"><?= $zmSlangHour ?></td>
<td align="right" class="smallhead"><?= $zmSlangDay ?></td>
<td align="right" class="smallhead"><?= $zmSlangWeek ?></td>
<td align="right" class="smallhead"><?= $zmSlangMonth ?></td>
<td align="right" class="smallhead"><?= $zmSlangArchive ?></td>
<td align="right" class="smallhead"><?= $zmSlangZones ?></td>
<td align="center" class="smallhead"><?= $zmSlangMark ?></td>
</tr>
<?php
$event_count = 0;
$hour_event_count = 0;
$day_event_count = 0;
$week_event_count = 0;
$month_event_count = 0;
$arch_event_count = 0;
$zone_count = 0;
foreach( $monitors as $monitor )
{
	$event_count += $monitor['EventCount'];
	$hour_event_count += $monitor['HourEventCount'];
	$day_event_count += $monitor['DayEventCount'];
	$week_event_count += $monitor['WeekEventCount'];
	$month_event_count += $monitor['MonthEventCount'];
	$arch_event_count += $monitor['ArchEventCount'];
	$zone_count += $monitor['ZoneCount'];
?>
<tr>
<td align="center" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=monitor&mid=".$monitor['Id']."', 'zmMonitor', ".$jws['monitor']['w'].", ".$jws['monitor']['h']." );", $monitor['Id'].'.', canView( 'Monitors' ) ) ?></td>
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
	if ( $monitor['RunMode'] == 'Triggered' )
	{
		$fclass .= "em";
	}
?>
<td align="left" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=watch&mid=".$monitor['Id']."', 'zmWatch".$monitor['Id']."', ".($monitor['Width']+$jws['watch']['w']).", ".($monitor['Height']+$jws['watch']['h'])." );", $monitor['Name'], canView( 'Stream' ) ) ?></td>
<td align="left" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=function&mid=".$monitor['Id']."', 'zmFunction', ".$jws['function']['w'].", ".$jws['function']['h']." );", "<span class=\"$fclass\">".$monitor['Function']."</span>", canEdit( 'Monitors' ) ) ?></td>
<?php if ( $monitor['Type'] == "Local" ) { ?>
<td align="left" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=monitor&mid=".$monitor['Id']."', 'zmMonitor', ".$jws['monitor']['w'].", ".$jws['monitor']['h']." );", "<span class=\"$dclass\">/dev/video".$monitor['Device']." (".$monitor['Channel'].")</span>", canEdit( 'Monitors' ) ) ?></td>
<?php } else { ?>
<td align="left" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=monitor&mid=".$monitor['Id']."', 'zmMonitor', ".$jws['monitor']['w'].", ".$jws['monitor']['h']." );", "<span class=\"$dclass\">".preg_replace( '/^.*@/', '', $monitor['Host'] )."</span>", canEdit( 'Monitors' ) ) ?></td>
<?php } ?>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&page=1&filter=1&trms=2&attr1=MonitorId&op1=%3d&val1=".$monitor['Id']."&cnj2=and&attr2=Archived&val2=0', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $monitor['EventCount'], canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&page=1&filter=1&trms=3&attr1=MonitorId&op1=%3d&val1=".$monitor['Id']."&cnj2=and&attr2=Archived&val2=0&cnj3=and&attr3=DateTime&op3=%3e%3d&val3=-1+hour', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $monitor['HourEventCount'], canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&page=1&filter=1&trms=3&attr1=MonitorId&op1=%3d&val1=".$monitor['Id']."&cnj2=and&attr2=Archived&val2=0&cnj3=and&attr3=DateTime&op3=%3e%3d&val3=-1+day', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $monitor['DayEventCount'], canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&page=1&filter=1&trms=3&attr1=MonitorId&op1=%3d&val1=".$monitor['Id']."&cnj2=and&attr2=Archived&val2=0&cnj3=and&attr3=DateTime&op3=%3e%3d&val3=-1+week', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $monitor['WeekEventCount'], canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&page=1&filter=1&trms=3&attr1=MonitorId&op1=%3d&val1=".$monitor['Id']."&cnj2=and&attr2=Archived&val2=0&cnj3=and&attr3=DateTime&op3=%3e%3d&val3=-1+month', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $monitor['MonthEventCount'], canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&page=1&filter=1&trms=2&attr1=MonitorId&op1=%3d&val1=".$monitor['Id']."&cnj2=and&attr2=Archived&val2=1', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $monitor['ArchEventCount'], canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=zones&mid=".$monitor['Id']."', 'zmZones', ".($monitor['Width']+$jws['zones']['w']).", ".($monitor['Height']+$jws['zones']['h'])." );", $monitor['ZoneCount'], canView( 'Monitors' ) ) ?></td>
<td align="center" class="text"><input type="checkbox" name="mark_mids[]" value="<?= $monitor['Id'] ?>" onClick="configureButton( document.monitor_form, 'mark_mids' );"<?php if ( !canEdit( 'Monitors' ) || isset($user['MonitorIds']) ) {?> disabled<?php } ?>></td>
</tr>
<?php
}
?>
<tr>
<td colspan="2" align="center">
<input type="button" value="<?= $zmSlangRefresh ?>" class="form" onClick="javascript: location.reload(true);">
</td>
<td colspan="2" align="center">
<input type="button" value="<?= $zmSlangAddNewMonitor ?>" class="form" onClick="javascript: newWindow( '<?= $PHP_SELF ?>?view=monitor', 'zmMonitor', <?= $jws['monitor']['w'] ?>, <?= $jws['monitor']['h'] ?>);"<?php if ( !canEdit( 'Monitors' ) || isset($user['MonitorIds']) ) {?> disabled<?php } ?>>
</td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&page=1&filter=1&trms=1&attr1=Archived&val1=0', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $event_count, canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&page=1&filter=1&trms=2&attr1=Archived&val1=0&cnj2=and&attr2=DateTime&op2=%3e%3d&val2=-1+hour', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $hour_event_count, canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&page=1&filter=1&trms=2&attr1=Archived&val1=0&cnj2=and&attr2=DateTime&op2=%3e%3d&val2=-1+day', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $day_event_count, canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&page=1&filter=1&trms=2&attr1=Archived&val1=0&cnj2=and&attr2=DateTime&op2=%3e%3d&val2=-1+week', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $week_event_count, canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&page=1&filter=1&trms=2&attr1=Archived&val1=0&cnj2=and&attr2=DateTime&op2=%3e%3d&val2=-1+month', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $month_event_count, canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&page=1&filter=1&trms=1&attr1=Archived&val1=1', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $arch_event_count, canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= $zone_count ?></td>
<td align="center"><input type="submit" name="delete_btn" value="<?= $zmSlangDelete ?>" class="form" disabled></td>
</tr>
</form>
</table>
</body>
</html>
