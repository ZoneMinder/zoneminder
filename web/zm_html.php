<?php

//
// ZoneMinder HTML interface file, $Date$, $Revision$
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

if ( !$bandwidth )
{
	$new_bandwidth = "low";
}

if ( $new_bandwidth )
{
	$bandwidth = $new_bandwidth;
	setcookie( "bandwidth", $new_bandwidth, time()+3600*24*30*12*10 );
}

require_once( 'zm_config.php' );
require_once( 'zm_db.php' );
require_once( 'zm_funcs.php' );
require_once( 'zm_actions.php' );

if ( !$view )
{
	$view = "console";
}

switch( $view )
{
	case "console" :
	{
		$running = daemonCheck();
		$status = $running?"Running":"Stopped";
		$new_status = $running?"stop":"start";

		if ( $stop )
		{
			packageControl( 'stop' );
		}
		if ( $start )
		{
			packageControl( 'start' );
		}

		if ( ZM_WEB_REFRESH_METHOD == "http" )
			header("Refresh: ".(($start||$stop)?1:REFRESH_MAIN)."; URL=$PHP_SELF" );
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
		header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");			// HTTP/1.0

		$db_now = strftime( "%Y-%m-%d %H:%M:%S" );
		$sql = "select M.*, count(E.Id) as EventCount, count(if(E.Archived,1,NULL)) as ArchEventCount, count(if(E.StartTime>'$db_now' - INTERVAL 1 HOUR && E.Archived = 0,1,NULL)) as HourEventCount, count(if(E.StartTime>'$db_now' - INTERVAL 1 DAY && E.Archived = 0,1,NULL)) as DayEventCount, count(if(E.StartTime>'$db_now' - INTERVAL 7 DAY && E.Archived = 0,1,NULL)) as WeekEventCount, count(if(E.StartTime>'$db_now' - INTERVAL 1 MONTH && E.Archived = 0,1,NULL)) as MonthEventCount from Monitors as M left join Events as E on E.MonitorId = M.Id group by M.Id order by M.Id";
		$result = mysql_query( $sql );
		if ( !$result )
			echo mysql_error();
		$monitors = array();
		$max_width = 0;
		$max_height = 0;
		$cycle_count = 0;
		while( $row = mysql_fetch_assoc( $result ) )
		{
			$row['zmc'] = zmcCheck( $row );
			$row['zma'] = zmaCheck( $row );
			//$sql = "select count(Id) as ZoneCount, count(if(Type='Active',1,NULL)) as ActZoneCount, count(if(Type='Inclusive',1,NULL)) as IncZoneCount, count(if(Type='Exclusive',1,NULL)) as ExcZoneCount, count(if(Type='Inactive',1,NULL)) as InactZoneCount from Zones where MonitorId = '$row[Id]'";
			$sql = "select count(Id) as ZoneCount from Zones where MonitorId = '$row[Id]'";
			$result2 = mysql_query( $sql );
			if ( !$result2 )
				echo mysql_error();
			$row2 = mysql_fetch_assoc( $result2 );
			$monitors[] = array_merge( $row, $row2 );
			if ( $row['Function'] != 'None' )
			{
				$cycle_count++;
				if ( $max_width < $row[Width] ) $max_width = $row[Width];
				if ( $max_height < $row[Height] ) $max_height = $row[Height];
			}
		}
		$montage_rows = intval(ceil($cycle_count/ZM_WEB_MONTAGE_MAX_COLS));
		$montage_cols = $cycle_count>=ZM_WEB_MONTAGE_MAX_COLS?ZM_WEB_MONTAGE_MAX_COLS:$cycle_count;
?>
<html>
<head>
<title>ZM - Console</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
window.resizeTo( <?= $jws['console']['w'] ?>, <?= $jws['console']['h'] ?> );
function newWindow(Url,Name,Width,Height)
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
function confirmStatus( new_status )
{
	return( confirm( 'Are you sure you wish to '+new_status+' all processes?' ) );
}
function confirmDelete()
{
	return( confirm( 'Warning, deleting a monitor also deletes all events and database entries associated with it.\nAre you sure you wish to delete?' ) );
}
<?php
		if ( ZM_WEB_REFRESH_METHOD == "javascript" )
		{
?>
window.setTimeout( 'window.location.reload(true)', <?= ($start||$stop)?250:(REFRESH_MAIN*1000) ?> );
<?php
		}
?>
</script>
</head>
<body>
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<tr>
<td class="smallhead" align="left"><?= date( "D jS M, g:ia" ) ?></td>
<td class="bighead" align="center"><strong>ZoneMinder Console - <?= $status ?> (<a href="javascript: if ( confirmStatus( '<?= $new_status ?>' ) ) location='<?= $PHP_SELF ?>?<?= $new_status ?>=1';"><?= $new_status ?></a>) - v<?= ZM_VERSION ?></strong></td>
<?php
	$uptime = shell_exec( 'uptime' );
	$load = '';
	preg_match( '/load average: ([\d.]+)/', $uptime, $matches );
?>
<td class="smallhead" align="right">Server Load: <?= $matches[1] ?></td>
</tr>
<tr>
<td class="smallhead" align="left">
<?php
	if ( $cycle_count > 1 )
	{
?>
<a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=cycle', 'zmCycle', <?= $max_width+$jws['cycle']['w'] ?>, <?= $max_height+$jws['cycle']['h'] ?> );"><?= count($monitors) ?> Monitor<?= count($monitors)==1?'':'s' ?></a>&nbsp;(<a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=montage', 'zmMontage', <?= ($montage_cols*$max_width)+$jws['montage']['w'] ?>, <?= ($montage_rows*(40+$max_height))+$jws['montage']['h'] ?> );">Montage</a>)
<?php
	}
	else
	{
?>
<?= count($monitors) ?> Monitor<?= count($monitors)==1?'':'s' ?>
<?php
	}
?>
</td>
<td class="smallhead" align="center">Configured for <strong><?= $bandwidth ?></strong> bandwidth (change to
<?php
		$bw_array = array( "high"=>1, "medium"=>1, "low"=>1 );
		unset( $bw_array[$bandwidth] );
		$bw_keys = array_keys( $bw_array );
?>
<a href="<?= $PHP_SELF ?>?new_bandwidth=<?= $bw_keys[0] ?>"><?= $bw_keys[0] ?></a>, 
<a href="<?= $PHP_SELF ?>?new_bandwidth=<?= $bw_keys[1] ?>"><?= $bw_keys[1] ?></a>)
<td class="smallhead" align="right"><a href="mailto:bugs@zoneminder.com?subject=ZoneMinder Bug (v<?= ZM_VERSION ?>)">Report Bug</a></td>
</tr>
</table>
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<form name="monitor_form" method="get" action="<?= $PHP_SELF ?>" onSubmit="return(confirmDelete());">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="delete">
<tr><td align="left" class="smallhead">Id</td>
<td align="left" class="smallhead">Name</td>
<td align="left" class="smallhead">Function</td>
<td align="left" class="smallhead">Source</td>
<!--<td align="left" class="smallhead">Dimensions</td>-->
<td align="right" class="smallhead">Events</td>
<td align="right" class="smallhead">Hour</td>
<td align="right" class="smallhead">Day</td>
<td align="right" class="smallhead">Week</td>
<td align="right" class="smallhead">Month</td>
<td align="right" class="smallhead">Archive</td>
<td align="right" class="smallhead">Zones</td>
<td align="center" class="smallhead">Mark</td>
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
			$event_count += $monitor[EventCount];
			$hour_event_count += $monitor[HourEventCount];
			$day_event_count += $monitor[DayEventCount];
			$week_event_count += $monitor[WeekEventCount];
			$month_event_count += $monitor[MonthEventCount];
			$arch_event_count += $monitor[ArchEventCount];
			$zone_count += $monitor[ZoneCount];
?>
<tr>
<td align="left" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=monitor&mid=<?= $monitor[Id] ?>', 'zmMonitor', <?= $jws['monitor']['w'] ?>, <?= $jws['monitor']['h'] ?> );"><?= $monitor[Id] ?>.</a></td>
<?php
			if ( !$monitor[zmc] )
			{
				$dclass = "redtext";
			}
			else
			{
				if ( !$monitor[zma] )
				{
					$dclass = "ambtext";
				}
				else
				{
					$dclass = "gretext";
				}
			}
			if ( $monitor['Function'] == 'Active' )
			{
				$fclass = "gretext";
			}
			elseif ( $monitor['Function'] == 'Passive' )
			{
				$fclass = "ambtext";
			}
			elseif ( $monitor['Function'] == 'X10' )
			{
				$fclass = "blutext";
			}
			else
			{
				$fclass = "redtext";
			}
?>
<td align="left" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=watch&mid=<?= $monitor[Id] ?>', 'zmWatch<?= $monitor[Name] ?>', <?= $monitor[Width]+$jws['watch']['w'] ?>, <?= $monitor[Height]+$jws['watch']['h'] ?> );"><?= $monitor[Name] ?></a></td>
<td align="left" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=function&mid=<?= $monitor[Id] ?>', 'zmFunction', <?= $jws['function']['w'] ?>, <?= $jws['function']['h'] ?> );"><span class="<?= $fclass ?>"><?= $monitor['Function'] ?></span></a></td>
<?php if ( $monitor[Type] == "Local" ) { ?>
<td align="left" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=monitor&mid=<?= $monitor[Id] ?>', 'zmMonitor', <?= $jws['monitor']['w'] ?>, <?= $jws['monitor']['h'] ?> );"><span class="<?= $dclass ?>">/dev/video<?= $monitor[Device] ?> (<?= $monitor[Channel] ?>)</span></a></td>
<?php } else { ?>
<td align="left" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=monitor&mid=<?= $monitor[Id] ?>', 'zmMonitor', <?= $jws['monitor']['w'] ?>, <?= $jws['monitor']['h'] ?> );"><span class="<?= $dclass ?>"><?= $monitor[Host] ?></span></a></td>
<?php } ?>
<td align="right" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=events&mid=<?= $monitor[Id] ?>&filter=1', 'zmEvents<?= $monitor[Name] ?>', <?= $jws['events']['w'] ?>, <?= $jws['events']['h'] ?> );"><?= $monitor[EventCount] ?></a></td>
<td align="right" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=events&mid=<?= $monitor[Id] ?>&filter=1&trms=2&attr1=Archived&val1=0&cnj2=and&attr2=DateTime&op2=%3e%3d&val2=last+hour', 'zmEvents<?= $monitor[Name] ?>', <?= $jws['events']['w'] ?>, <?= $jws['events']['h'] ?> );"><?= $monitor[HourEventCount] ?></a></td>
<td align="right" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=events&mid=<?= $monitor[Id] ?>&filter=1&trms=2&attr1=Archived&val1=0&cnj2=and&attr2=DateTime&op2=%3e%3d&val2=last+day', 'zmEvents<?= $monitor[Name] ?>', <?= $jws['events']['w'] ?>, <?= $jws['events']['h'] ?> );"><?= $monitor[DayEventCount] ?></a></td>
<td align="right" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=events&mid=<?= $monitor[Id] ?>&filter=1&trms=2&attr1=Archived&val1=0&cnj2=and&attr2=DateTime&op2=%3e%3d&val2=last+week', 'zmEvents<?= $monitor[Name] ?>', <?= $jws['events']['w'] ?>, <?= $jws['events']['h'] ?> );"><?= $monitor[WeekEventCount] ?></a></td>
<td align="right" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=events&mid=<?= $monitor[Id] ?>&filter=1&trms=2&attr1=Archived&val1=0&cnj2=and&attr2=DateTime&op2=%3e%3d&val2=last+month', 'zmEvents<?= $monitor[Name] ?>', <?= $jws['events']['w'] ?>, <?= $jws['events']['h'] ?> );"><?= $monitor[MonthEventCount] ?></a></td>
<td align="right" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=events&mid=<?= $monitor[Id] ?>&filter=1&trms=1&attr1=Archived&val1=1', 'zmEvents<?= $monitor[Name] ?>', <?= $jws['events']['w'] ?>, <?= $jws['events']['h'] ?> );"><?= $monitor[ArchEventCount] ?></a></td>
<td align="right" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=zones&mid=<?= $monitor[Id] ?>', 'zmZones', <?= $monitor[Width]+$jws['zones']['w'] ?>, <?= $monitor[Height]+$jws['zones']['h'] ?> );"><?= $monitor[ZoneCount] ?></a></td>
<td align="center" class="text"><input type="checkbox" name="mark_mids[]" value="<?= $monitor[Id] ?>" onClick="configureButton( document.monitor_form, 'mark_mids' );"></td>
</tr>
<?php
		}
?>
<tr>
<td colspan="2" align="center">
<input type="button" value="Refresh" class="form" onClick="javascript: location.reload(true);">
</td>
<td colspan="2" align="center">
<input type="button" value="Add New Monitor" class="form" onClick="javascript: newWindow( '<?= $PHP_SELF ?>?view=monitor&zid=-1', 'zmMonitor', <?= $jws['monitor']['w'] ?>, <?= $jws['monitor']['h'] ?>);">
</td>
<td align="right" class="text"><?= $event_count ?></td>
<td align="right" class="text"><?= $hour_event_count ?></td>
<td align="right" class="text"><?= $day_event_count ?></td>
<td align="right" class="text"><?= $week_event_count ?></td>
<td align="right" class="text"><?= $month_event_count ?></td>
<td align="right" class="text"><?= $arch_event_count ?></td>
<td align="right" class="text"><?= $zone_count ?></td>
<td align="center"><input type="submit" name="delete_btn" value="Delete" class="form" disabled></td>
</tr>
</form>
</table>
</body>
</html>
<?php
		break;
	}
	case "cycle" :
	{
		if ( !$mode )
		{
			if ( canStream() )
				$mode = "stream";
			else
				$mode = "still";
		}

		$result = mysql_query( "select * from Monitors where Function != 'None' order by Id" );
		$monitors = array();
		$mon_idx = 0;
		while( $row = mysql_fetch_assoc( $result ) )
		{
			if ( $mid && $row[Id] == $mid )
				$mon_idx = count($monitors);
			$monitors[] = $row;
		}

		$monitor = $monitors[$mon_idx];
		$next_mid = $mon_idx==(count($monitors)-1)?$monitors[0][Id]:$monitors[$mon_idx+1][Id];

		// Prompt an image to be generated
		chdir( ZM_DIR_IMAGES );
		$status = exec( escapeshellcmd( ZMU_PATH." -m $monitor[Id] -i" ) );
											 
		if ( ZM_WEB_REFRESH_METHOD == "http" )
			header("Refresh: ".REFRESH_CYCLE."; URL=$PHP_SELF?view=cycle&mid=$next_mid&mode=$mode" );
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
		header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");			  // HTTP/1.0
?>
<html>
<head>
<title>ZM - Cycle Watch</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function newWindow(Url,Name,Width,Height)
{
	var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function closeWindow()
{
	top.window.close();
}
<?php
		if ( ZM_WEB_REFRESH_METHOD == "javascript" )
		{
	?>
window.setTimeout( "window.location.replace( '<?= "$PHP_SELF?view=cycle&mid=$next_mid&mode=$mode" ?>', <?= REFRESH_CYCLE*1000 ?> );
<?php
		}
?>
</script>
</head>
<body>
<table width="96%" align="center" border="0" cellspacing="0" cellpadding="4">
<tr>
<td width="33%" align="left" class="text"><b><?= $monitor[Name] ?></b></td>
<?php if ( $mode == "stream" ) { ?>
<td width="34%" align="center" class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=still&mid=<?= $mid ?>">Stills</a></td>
<?php } elseif ( canStream() ) { ?>
<td width="34%" align="center" class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=stream&mid=<?= $mid ?>">Stream</a></td>
<?php } else { ?>
<td width="34%" align="center" class="text">&nbsp;</td>
<?php } ?>
<td width="33%" align="right" class="text"><a href="javascript: closeWindow();">Close</a></td>
</tr>
<?php
		if ( $mode == "stream" )
		{
			$stream_src = ZM_PATH_ZMS."?monitor=$monitor[Id]&idle=".STREAM_IDLE_DELAY."&refresh=".STREAM_FRAME_DELAY."&ttl=".REFRESH_CYCLE;
			if ( isNetscape() )
			{
?>
<tr><td colspan="3" align="center"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=watch&mid=<?= $monitor[Id] ?>', 'zmWatch<?= $monitor[Name] ?>', <?= $monitor[Width]+$jws['watch']['w'] ?>, <?= $monitor[Height]+$jws['watch']['h'] ?> );"><img src="<?= $stream_src ?>" border="0" width="<?= $monitor[Width] ?>" height="<?= $monitor[Height] ?>"></a></td></tr>
<?php
			}
			else
			{
?>
<tr><td colspan="3" align="center"><applet code="com.charliemouse.cambozola.Viewer" archive="<?= ZM_PATH_CAMBOZOLA ?>" align="middle" width="<?= $monitor[Width] ?>" height="<?= $monitor[Height] ?>"><param name="url" value="<?= $stream_src ?>"></applet></td></tr>
<?php
			}
		}
		else
		{
?>
<tr><td colspan="3" align="center"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=watch&mid=<?= $monitor[Id] ?>', 'zmWatch<?= $monitor[Name] ?>', <?= $monitor[Width]+$jws['watch']['w'] ?>, <?= $monitor[Height]+$jws['watch']['h'] ?> );"><img src="<?= ZM_DIR_IMAGES.'/'.$monitor[Name] ?>.jpg" border="0" width="<?= $monitor[Width] ?>" height="<?= $monitor[Height] ?>"></a></td></tr>
<?php
		}
?>
</table>
</body>
</html>
<?php
		break;
	}
	case "montage" :
	{
		$result = mysql_query( "select * from Monitors where Function != 'None' order by Id" );
		$monitors = array();
		while( $row = mysql_fetch_assoc( $result ) )
		{
			$monitors[] = $row;
		}
		$rows = intval(ceil(count($monitors)/ZM_WEB_MONTAGE_MAX_COLS));
		$cols = count($monitors)>=ZM_WEB_MONTAGE_MAX_COLS?ZM_WEB_MONTAGE_MAX_COLS:count($monitors);
		$widths = array();
		$heights = array();
		for ( $i = 0; $i < count($monitors); $i++ )
		{
			$monitor = $monitors[$i];
			$frame_height = $monitor[Height]+16;
			$row = $i/ZM_WEB_MONTAGE_MAX_COLS;
			$col = $i%ZM_WEB_MONTAGE_MAX_COLS;
			if ( $frame_height > $heights[$row] )
				$heights[$row] = $frame_height;
			if ( $monitor[Width] > $widths[$col] )
				$widths[$col] = $monitor[Width];
		}
		$row_spec = join( ',', $heights );
		$col_spec = join( ',', $widths );
?>
<html>
<head>
<title>ZM - Montage</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
//window.resizeTo( <?= $jws['montage']['w']*$cols ?>, <?= $jws['montage']['h']*$rows ?> );
window.focus();
</script>
</head>
<frameset rows="<?= $row_spec ?>" cols="<?= $col_spec ?>" border="1" frameborder="no" framespacing="0">
<?php
		for ( $row = 0; $row < $rows; $row++ )
		{
			for ( $col = 0; $col < $cols; $col++ )
			{
				$i = ($row*$cols)+$col;
				if ( $i < count($monitors) )
				{
					$monitor = $monitors[$i];
?>
<frameset rows="*,16" cols="100%" border="1" frameborder="no" framespacing="0">
<frame src="<?= $PHP_SELF ?>?view=montagefeed&mid=<?= $monitor[Id] ?>" marginwidth="0" marginheight="0" name="MonitorStream" scrolling="no">
<frame src="<?= $PHP_SELF ?>?view=montagestatus&mid=<?= $monitor[Id] ?>" marginwidth="0" marginheight="0" name="MonitorStatus" scrolling="no">
</frameset>
<?php
				}
			}
		}
?>
</frameset>
<?php
		break;
	}
	case "montagehead" :
	{
?>
<html>
<head>
<title>ZM - Montage Header</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function closeWindow()
{
	top.window.close();
}
</script>
</head>
<body>
<table width="96%" align="center" border="0" cellspacing="0" cellpadding="4">
</table>
</body>
</html>
<?php
		break;
	}
	case "montagefeed" :
	{
		if ( !$mode )
		{
			if ( canStream() )
				$mode = "stream";
			else
				$mode = "still";
		}

		$result = mysql_query( "select * from Monitors where Id = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );

		if ( $mode != "stream" )
		{
			// Prompt an image to be generated
			chdir( ZM_DIR_IMAGES );
			$status = exec( escapeshellcmd( ZMU_PATH." -m $mid -i" ) );
			chdir( '..' );
			if ( ZM_WEB_REFRESH_METHOD == "http" )
				header("Refresh: ".REFRESH_IMAGE."; URL=$PHP_SELF?view=montagefeed&mid=$mid&mode=still" );
		}
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
		header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");			  // HTTP/1.0
?>
<html>
<head>
<title>ZM - <?= $monitor[Name] ?> - MontageFeed</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
<?php
		if ( $mode != "stream" && ZM_WEB_REFRESH_METHOD == "javascript" )
		{
?>
window.setTimeout( "window.location.reload(true)", <?= REFRESH_IMAGE*1000 ?> );
<?php
		}
?>
</script>
</head>
<body>
<table width="96%" align="center" border="0" cellspacing="0" cellpadding="4">
<tr>
<td width="50%" align="center" class="text"><b><?= $monitor[Name] ?></b></td>
<?php if ( $mode == "stream" ) { ?>
<td width="50%" align="center" class="text"><a href="<?= $PHP_SELF ?>?view=montagefeed&mode=still&mid=<?= $mid ?>">Stills</a></td>
<?php } elseif ( canStream() ) { ?>
<td width="50%" align="center" class="text"><a href="<?= $PHP_SELF ?>?view=montagefeed&mode=stream&mid=<?= $mid ?>">Stream</a></td>
<?php } else { ?>
<td width="50%" align="center" class="text">&nbsp;</td>
<?php } ?>
</tr>
<?php
		if ( $mode == "stream" )
		{
			$stream_src = ZM_PATH_ZMS."?monitor=$monitor[Id]&idle=".STREAM_IDLE_DELAY."&refresh=".STREAM_FRAME_DELAY;
			if ( isNetscape() )
			{
?>
<tr><td colspan="2" align="center"><img src="<?= $stream_src ?>" border="0" width="<?= $monitor[Width] ?>" height="<?= $monitor[Height] ?>"></td></tr>
<?php
			}
			else
			{
?>
<tr><td colspan="2" align="center"><applet code="com.charliemouse.cambozola.Viewer" archive="<?= ZM_PATH_CAMBOZOLA ?>" align="middle" width="<?= $monitor[Width] ?>" height="<?= $monitor[Height] ?>"><param name="url" value="<?= $stream_src ?>"></applet></td></tr>
<?php
			}
		}
		else
		{
?>
<tr><td colspan="2" align="center"><img src="<?= ZM_DIR_IMAGES.'/'.$monitor[Name] ?>.jpg" border="0" width="<?= $monitor[Width] ?>" height="<?= $monitor[Height] ?>"></td></tr>
<?php
		}
?>
</table>
</body>
</html>
<?php
		break;
	}
	case "montagestatus" :
	{
		$zmu_command = ZMU_PATH." -m $mid -s -f";
		$zmu_output = exec( escapeshellcmd( $zmu_command ) );
		list( $status, $fps ) = split( ' ', $zmu_output );
		$status_string = "Unknown";
		$fps_string = "--.--";
		$class = "text";
		if ( $status == 0 )
		{
			$status_string = "Idle";
		}
		elseif ( $status == 1 )
		{
			$status_string = "Alarm";
			$class = "redtext";
		}
		elseif ( $status == 2 )
		{
			$status_string = "Alert";
			$class = "ambtext";
		}
		$fps_string = sprintf( "%.2f", $fps );
		$new_alarm = ( $status > 0 && $last_status == 0 );
		$old_alarm = ( $status == 0 && $last_status > 0 );

		$refresh = (isset($force)||$forced||$status)?1:REFRESH_STATUS;
		$url = "$PHP_SELF?view=montagestatus&mid=$mid&last_status=$status";
		if ( ZM_WEB_REFRESH_METHOD == "http" )
			header("Refresh: $refresh; URL=$url" );
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
		header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");			  // HTTP/1.0
?>
<html>
<head>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
<?php
		if ( ZM_WEB_POPUP_ON_ALARM && $new_alarm )
		{
?>
top.window.focus();
<?php
		}
		if ( ZM_WEB_REFRESH_METHOD == "javascript" )
		{
?>
window.setTimeout( "window.location.reload(true)", <?= $refresh*1000 ?> );
<?php
		}
?>
</script>
</head>
<body>
<table width="90%" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
<td width="30%" class="text" align="left">&nbsp;</td>
<td width="40%" class="<?= $class ?>" align="center" valign="middle">Status:&nbsp;<?= $status_string ?>&nbsp;-&nbsp;<?= $fps_string ?>&nbsp;fps</td>
<td width="30%" align="right" class="text">&nbsp;</td>
</tr>
</table>
<?php
		if ( ZM_WEB_SOUND_ON_ALARM && $status == 1 )
		{
?>
<embed src="<?= ZM_DIR_SOUNDS.'/'.ZM_WEB_ALARM_SOUND ?>" autostart="yes" hidden="true"></embed>
<?php
		}
?>
</body>
</html>
<?php
		break;
	}
	case "watch" :
	{
		$result = mysql_query( "select * from Monitors where Id = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );
?>
<html>
<head>
<title>ZM - <?= $monitor[Name] ?> - Watch</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
//opener.location.reload();
window.focus();
</script>
</head>
<frameset rows="<?= $monitor[Height]+32 ?>,16,*" border="1" frameborder="no" framespacing="0">
<frame src="<?= $PHP_SELF ?>?view=watchfeed&mid=<?= $monitor[Id] ?>" marginwidth="0" marginheight="0" name="MonitorStream" scrolling="no">
<frame src="<?= $PHP_SELF ?>?view=watchstatus&mid=<?= $monitor[Id] ?>" marginwidth="0" marginheight="0" name="MonitorStatus" scrolling="no">
<frame src="<?= $PHP_SELF ?>?view=watchevents&max_events=<?= MAX_EVENTS ?>&mid=<?= $monitor[Id] ?>" marginwidth="0" marginheight="0" name="MonitorEvents" scrolling="auto">
</frameset>
<?php
		break;
	}
	case "watchfeed" :
	{
		if ( !$mode )
		{
			if ( canStream() )
				$mode = "stream";
			else
				$mode = "still";
		}

		$result = mysql_query( "select * from Monitors where Id = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );

		if ( $mode != "stream" )
		{
			// Prompt an image to be generated
			chdir( ZM_DIR_IMAGES );
			$status = exec( escapeshellcmd( ZMU_PATH." -m $mid -i" ) );
			chdir( '..' );
			if ( ZM_WEB_REFRESH_METHOD == "http" )
				header("Refresh: ".REFRESH_IMAGE."; URL=$PHP_SELF?view=watchfeed&mid=$mid&mode=still" );
		}
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
		header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");			  // HTTP/1.0
?>
<html>
<head>
<title>ZM - <?= $monitor[Name] ?> - WatchFeed</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function newWindow(Url,Name,Width,Height)
{
   	var Name = window.open(Url,Name,"resizable,width="+Width+",height="+Height);
}
function closeWindow()
{
	top.window.close();
}
<?php
		if ( $mode != "stream" && ZM_WEB_REFRESH_METHOD == "javascript" )
		{
?>
window.setTimeout( "window.location.reload(true)", <?= REFRESH_IMAGE*1000 ?> );
<?php
		}
?>
</script>
</head>
<body>
<table width="96%" align="center" border="0" cellspacing="0" cellpadding="4">
<tr>
<td width="25%" align="left" class="text"><b><?= $monitor[Name] ?></b></td>
<?php if ( $monitor[Type] == "Local" ) { ?>
<td width="25%" align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=settings&mid=<?= $monitor[Id] ?>', 'zmSettings<?= $monitor[Name] ?>', <?= $jws['settings']['w'] ?>, <?= $jws['settings']['h'] ?> );">Settings</a></td>
<?php } else { ?>
<td width="25%" align="center" class="text">&nbsp;</td>
<?php } ?>
<?php if ( $mode == "stream" ) { ?>
<td width="25%" align="center" class="text"><a href="<?= $PHP_SELF ?>?view=watchfeed&mode=still&mid=<?= $mid ?>">Stills</a></td>
<?php } elseif ( canStream() ) { ?>
<td width="25%" align="center" class="text"><a href="<?= $PHP_SELF ?>?view=watchfeed&mode=stream&mid=<?= $mid ?>">Stream</a></td>
<?php } else { ?>
<td width="25%" align="center" class="text">&nbsp;</td>
<?php } ?>
<td width="25%" align="right" class="text"><a href="javascript: closeWindow();">Close</a></td>
</tr>
<?php
		if ( $mode == "stream" )
		{
			$stream_src = ZM_PATH_ZMS."?monitor=$monitor[Id]&idle=".STREAM_IDLE_DELAY."&refresh=".STREAM_FRAME_DELAY;
			if ( isNetscape() )
			{
?>
<tr><td colspan="4" align="center"><img src="<?= $stream_src ?>" border="0" width="<?= $monitor[Width] ?>" height="<?= $monitor[Height] ?>"></td></tr>
<?php
			}
			else
			{
?>
<tr><td colspan="4" align="center"><applet code="com.charliemouse.cambozola.Viewer" archive="<?= ZM_PATH_CAMBOZOLA ?>" align="middle" width="<?= $monitor[Width] ?>" height="<?= $monitor[Height] ?>"><param name="url" value="<?= $stream_src ?>"></applet></td></tr>
<?php
			}
		}
		else
		{
?>
<tr><td colspan="4" align="center"><img src="<?= ZM_DIR_IMAGES.'/'.$monitor[Name] ?>.jpg" border="0" width="<?= $monitor[Width] ?>" height="<?= $monitor[Height] ?>"></td></tr>
<?php
		}
?>
</table>
</body>
</html>
<?php
		break;
	}
	case "settings" :
	{
		$result = mysql_query( "select * from Monitors where Id = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );

		$zmu_command = ZMU_PATH." -m $mid -B -C -H -O";
		$zmu_output = exec( escapeshellcmd( $zmu_command ) );
		list( $brightness, $contrast, $hue, $colour ) = split( ' ', $zmu_output );
?>
<html>
<head>
<title>ZM - <?= $monitor[Name] ?> - Settings</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
<?php
		if ( $refresh_parent )
		{
?>
opener.location.reload(true);
<?php
		}
?>
window.focus();
function validateForm(theForm)
{
	return( true );
}

function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td colspan="2" align="left" class="head">Monitor <?= $monitor[Name] ?> - Settings</td>
</tr>
<form name="settings_form" method="get" action="<?= $PHP_SELF ?>" onsubmit="return validateForm( document.settings_form )">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="settings">
<input type="hidden" name="mid" value="<?= $mid ?>">
<tr>
<td align="right" class="smallhead">Parameter</td><td align="left" class="smallhead">Value</td>
</tr>
<tr><td align="right" class="text">Brightness</td><td align="left" class="text"><input type="text" name="new_brightness" value="<?= $brightness ?>" size="8" class="form"></td></tr>
<tr><td align="right" class="text">Contrast</td><td align="left" class="text"><input type="text" name="new_contrast" value="<?= $contrast ?>" size="8" class="form"></td></tr>
<tr><td align="right" class="text">Hue</td><td align="left" class="text"><input type="text" name="new_hue" value="<?= $hue ?>" size="8" class="form"></td></tr>
<tr><td align="right" class="text">Colour</td><td align="left" class="text"><input type="text" name="new_colour" value="<?= $colour ?>" size="8" class="form"></td></tr>
<tr>
<td colspan="2" align="right"><input type="submit" value="Save" class="form">&nbsp;&nbsp;<input type="button" value="Cancel" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
<?php
		break;
	}
	case "watchstatus" :
	{
		$zmu_command = ZMU_PATH." -m $mid -s -f";
		if ( isset($force) )
		{
			$zmu_command .= ($force?" -a":" -c"); 
		}

		$zmu_output = exec( escapeshellcmd( $zmu_command ) );
		list( $status, $fps ) = split( ' ', $zmu_output );
		$status_string = "Unknown";
		$fps_string = "--.--";
		$class = "text";
		if ( $status == 0 )
		{
			$status_string = "Idle";
		}
		elseif ( $status == 1 )
		{
			$status_string = "Alarm";
			$class = "redtext";
		}
		elseif ( $status == 2 )
		{
			$status_string = "Alert";
			$class = "ambtext";
		}
		$fps_string = sprintf( "%.2f", $fps );
		$new_alarm = ( $status > 0 && $last_status == 0 );
		$old_alarm = ( $status == 0 && $last_status > 0 );

		$refresh = (isset($force)||$forced||$status)?1:REFRESH_STATUS;
		$url = "$PHP_SELF?view=watchstatus&mid=$mid&last_status=$status".(($force||$forced)?"&forced=1":"");
		if ( ZM_WEB_REFRESH_METHOD == "http" )
			header("Refresh: $refresh; URL=$url" );
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
		header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");			  // HTTP/1.0
?>
<html>
<head>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
<?php
		if ( ZM_WEB_POPUP_ON_ALARM && $new_alarm )
		{
?>
top.window.focus();
<?php
		}
		if ( $old_alarm )
		{
?>
parent.frames[2].location.reload(true);
<?php
		}
		if ( ZM_WEB_REFRESH_METHOD == "javascript" )
		{
?>
window.setTimeout( "window.location.replace( '<?= $url ?>' )", <?= $refresh*1000 ?> );
<?php
		}
?>
</script>
</head>
<body>
<table width="90%" align="center" border="0" cellpadding="0" cellspacing="0">
<tr>
<td width="30%" class="text" align="left">&nbsp;</td>
<td width="40%" class="<?= $class ?>" align="center" valign="middle">Status:&nbsp;<?= $status_string ?>&nbsp;-&nbsp;<?= $fps_string ?>&nbsp;fps</td>
<?php
		if ( $force || $forced )
		{
?>
<td width="30%" align="right" class="text"><a href="<?= $PHP_SELF ?>?view=watchstatus&mid=<?= $mid ?>&last_status=$status&force=0">Cancel Forced Alarm</a></td>
<?php
		}
		elseif ( zmaCheck( $mid ) )
		{
?>
<td width="30%" align="right" class="text"><a href="<?= $PHP_SELF ?>?view=watchstatus&mid=<?= $mid ?>&last_status=$status&force=1">Force Alarm</a></td>
<?php
		}
		else
		{
?>
<td width="30%" align="right" class="text">&nbsp;</td>
<?php
		}
?>
</tr>
</table>
<?php
		if ( ZM_WEB_SOUND_ON_ALARM && $status == 1 )
		{
?>
<embed src="<?= ZM_DIR_SOUNDS.'/'.ZM_WEB_ALARM_SOUND ?>" autostart="yes" hidden="true"></embed>
<?php
		}
?>
</body>
</html>
<?php
		break;
	}
	case "watchevents" :
	{
		switch( $sort_field )
		{
			case 'Id' :
				$sort_column = "E.Id";
				break;
			case 'Name' :
				$sort_column = "E.Name";
				break;
			case 'Time' :
				$sort_column = "E.StartTime";
				break;
			case 'Secs' :
				$sort_column = "E.Length";
				break;
			case 'Frames' :
				$sort_column = "E.Frames";
				break;
			case 'Score' :
				$sort_column = "E.AvgScore";
				break;
			default:
				$sort_field = "Time";
				$sort_column = "E.StartTime";
				break;
		}
		$sort_order = $sort_asc?"asc":"desc";
		if ( !$sort_asc )
			$sort_asc = 0;
		if ( ZM_WEB_REFRESH_METHOD == "http" )
			header("Refresh: ".REFRESH_EVENTS."; URL=$PHP_SELF?view=watchevents&mid=$mid&max_events=".MAX_EVENTS );
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
		header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");			  // HTTP/1.0
?>
<html>
<head>
<title>ZM - <?= $monitor ?> - Events</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function newWindow(Url,Name,Width,Height)
{
   	var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function closeWindow()
{
	top.window.close();
}
function checkAll(form,name){
	for (var i = 0; i < form.elements.length; i++)
		if (form.elements[i].name.indexOf(name) == 0)
			form.elements[i].checked = 1;
	form.delete_btn.disabled = false;
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
<?php
		if ( ZM_WEB_REFRESH_METHOD == "javascript" )
		{
?>
window.setTimeout( "window.location.replace( '<?= "$PHP_SELF?view=watchevents&mid=$mid&max_events=".MAX_EVENTS ?>' )", <?= REFRESH_EVENTS*1000 ?> );
<?php
		}
?>
</script>
</head>
<body>
<form name="event_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="max_events" value="<?= $max_events ?>">
<center><table width="96%" align="center" border="0" cellspacing="1" cellpadding="0">
<tr>
<td valign="top"><table border="0" cellspacing="0" cellpadding="0" width="100%">
<?php
		$result = mysql_query( "select * from Monitors where Id = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );

		$sql = "select E.Id,E.Name,E.StartTime,E.Length,E.Frames,E.AlarmFrames,E.AvgScore,E.MaxScore from Monitors as M left join Events as E on M.Id = E.MonitorId where M.Id = '$mid' and E.Archived = 0";
		$sql .= " order by $sort_column $sort_order";
		$sql .= " limit 0,$max_events";
		$result = mysql_query( $sql );
		if ( !$result )
		{
			die( mysql_error() );
		}
		$n_rows = mysql_num_rows( $result );
?>
<tr>
<td class="text"><b>Last <?= $n_rows ?> events</b></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=events&mid=<?= $monitor[Id] ?>&filter=1&trms=0', 'zmEvents<?= $monitor[Name] ?>', <?= $jws['events']['w'] ?>, <?= $jws['events']['h'] ?> );">All</a></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=events&mid=<?= $monitor[Id] ?>&filter=1&trms=1&attr1=Archived&val1=1', 'zmEvents<?= $monitor[Name] ?>', <?= $jws['events']['w'] ?>, <?= $jws['events']['h'] ?> );">Archive</a></td>
<td align="right" class="text"><a href="javascript: checkAll( document.event_form, 'mark_eids' );">Check All</a></td>
</tr>
<tr><td colspan="5" class="text">&nbsp;</td></tr>
<tr><td colspan="5"><table border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="#7F7FB2">
<tr align="center" bgcolor="#FFFFFF">
<td width="4%" class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Id&sort_asc=<?= $sort_field == 'Id'?!$sort_asc:0 ?>">Id<?php if ( $sort_field == "Id" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td width="24%" class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Name&sort_asc=<?= $sort_field == 'Name'?!$sort_asc:0 ?>">Name<?php if ( $sort_field == "Name" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Time&sort_asc=<?= $sort_field == 'Time'?!$sort_asc:0 ?>">Time<?php if ( $sort_field == "Time" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Secs&sort_asc=<?= $sort_field == 'Secs'?!$sort_asc:0 ?>">Secs<?php if ( $sort_field == "Secs" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Frames&sort_asc=<?= $sort_field == 'Frames'?!$sort_asc:0 ?>">Frames<?php if ( $sort_field == "Frames" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Score&sort_asc=<?= $sort_field == 'Score'?!$sort_asc:0 ?>">Score<?php if ( $sort_field == "Score" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text">Mark</td>
</tr>
<?php
		while( $row = mysql_fetch_assoc( $result ) )
		{
?>
<tr bgcolor="#FFFFFF">
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=event&mid=<?= $mid ?>&eid=<?= $row[Id] ?>', 'zmEvent', <?= $jws['event']['w'] ?>, <?= $jws['event']['h'] ?> );"><?= $row[Id] ?></a></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=event&mid=<?= $mid ?>&eid=<?= $row[Id] ?>', 'zmEvent', <?= $jws['event']['w'] ?>, <?= $jws['event']['h'] ?> );"><?= $row[Name] ?></a></td>
<td align="center" class="text"><?= strftime( "%m/%d %H:%M:%S", strtotime($row[StartTime]) ) ?></td>
<td align="center" class="text"><?= $row[Length] ?></td>
<td align="center" class="text"><?= $row[Frames] ?>/<?= $row[AlarmFrames] ?></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=image&eid=<?= $row[Id] ?>&fid=0', 'zmImage', <?= $monitor[Width]+$jws['image']['w'] ?>, <?= $monitor[Height]+$jws['image']['h'] ?> );"><?= $row[AvgScore] ?>/<?= $row[MaxScore] ?></a></td>
<td align="center" class="text"><input type="checkbox" name="mark_eids[]" value="<?= $row[Id] ?>" onClick="configureButton( document.event_form, 'mark_eids' );"></td>
</tr>
<?php
		}
?>
</table></td></tr>
</table></td>
</tr>
<tr><td align="right"><input type="submit" name="delete_btn" value="Delete" class="form" disabled></td></tr>
</table></center>
</form>
</body>
</html>
<?php
		break;
	}
	case "events" :
	{
		switch( $sort_field )
		{
			case 'Id' :
				$sort_column = "E.Id";
				break;
			case 'Name' :
				$sort_column = "E.Name";
				break;
			case 'Time' :
				$sort_column = "E.StartTime";
				break;
			case 'Secs' :
				$sort_column = "E.Length";
				break;
			case 'Frames' :
				$sort_column = "E.Frames";
				break;
			case 'AlarmFrames' :
				$sort_column = "E.AlarmFrames";
				break;
			case 'TotScore' :
				$sort_column = "E.TotScore";
				break;
			case 'AvgScore' :
				$sort_column = "E.AvgScore";
				break;
			case 'MaxScore' :
				$sort_column = "E.MaxScore";
				break;
			default:
				$sort_field = "Time";
				$sort_column = "E.StartTime";
				break;
		}
		$sort_order = $sort_asc?"asc":"desc";
		if ( !$sort_asc ) $sort_asc = 0;

		$result = mysql_query( "select * from Monitors where Id = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );

		// XXX
		$sql = "select E.Id,E.Name,E.StartTime,E.Length,E.Frames,E.AlarmFrames,E.TotScore,E.AvgScore,E.MaxScore,E.Archived,E.LearnState from Monitors as M, Events as E where M.Id = '$mid' and M.Id = E.MonitorId";
		$filter_query = ''; 
		$filter_sql = '';
		$filter_fields = '';
		if ( $trms )
		{
			$filter_query .= "&trms=$trms";
			$filter_fields .= '<input type="hidden" name="trms" value="'.$trms.'">'."\n";
		}
		for ( $i = 1; $i <= $trms; $i++ )
		{
			$conjunction_name = "cnj$i";
			$obracket_name = "obr$i";
			$cbracket_name = "cbr$i";
			$attr_name = "attr$i";
			$op_name = "op$i";
			$value_name = "val$i";
			if ( $$conjunction_name )
			{
				$filter_query .= "&$conjunction_name=".$$conjunction_name;
				$filter_sql .= " ".$$conjunction_name." ";
				$filter_fields .= '<input type="hidden" name="'.$conjunction_name.'" value="'.$$conjunction_name.'">'."\n";
			}
			if ( $$obracket_name )
			{
				$filter_query .= "&$obracket_name=".$$obracket_name;
				$filter_sql .= str_repeat( "(", $$obracket_name );
				$filter_fields .= '<input type="hidden" name="'.$obracket_name.'" value="'.$$obracket_name.'">'."\n";
			}
			if ( $$attr_name )
			{
				$filter_query .= "&$attr_name=".$$attr_name;
				$filter_fields .= '<input type="hidden" name="'.$attr_name.'" value="'.$$attr_name.'">'."\n";
				switch ( $$attr_name )
				{
					case 'DateTime':
						$dt_val = strtotime( $$value_name );
						$filter_sql .= "E.StartTime ".$$op_name." from_unixtime( $dt_val )";
						$filter_query .= "&$op_name=".urlencode($$op_name);
						$filter_fields .= '<input type="hidden" name="'.$op_name.'" value="'.$$op_name.'">'."\n";
						break;
					case 'Date':
						$dt_val = strtotime( $$value_name );
						$filter_sql .= "to_days( E.StartTime ) ".$$op_name." to_days( from_unixtime( $dt_val ) )";
						$filter_query .= "&$op_name=".urlencode($$op_name);
						$filter_fields .= '<input type="hidden" name="'.$op_name.'" value="'.$$op_name.'">'."\n";
						break;
					case 'Time':
						$dt_val = strtotime( $$value_name );
						$filter_sql .= "extract( hour_second from E.StartTime ) ".$$op_name." extract( hour_second from from_unixtime( $dt_val ) )";
						$filter_query .= "&$op_name=".urlencode($$op_name);
						$filter_fields .= '<input type="hidden" name="'.$op_name.'" value="'.$$op_name.'">'."\n";
						break;
					case 'Weekday':
						$dt_val = strtotime( $$value_name );
						$filter_sql .= "weekday( E.StartTime ) ".$$op_name." weekday( from_unixtime( $dt_val ) )";
						$filter_query .= "&$op_name=".urlencode($$op_name);
						$filter_fields .= '<input type="hidden" name="'.$op_name.'" value="'.$$op_name.'">'."\n";
						break;
					case 'Length':
					case 'Frames':
					case 'AlarmFrames':
					case 'TotScore':
					case 'AvgScore':
					case 'MaxScore':
						$filter_sql .= "E.".$$attr_name." ".$$op_name." ".$$value_name;
						$filter_query .= "&$op_name=".urlencode($$op_name);
						$filter_fields .= '<input type="hidden" name="'.$op_name.'" value="'.$$op_name.'">'."\n";
						break;
					case 'Archived':
						$filter_sql .= "E.Archived = ".$$value_name;
						break;
				}
				$filter_query .= "&$value_name=".urlencode($$value_name);
				$filter_fields .= '<input type="hidden" name="'.$value_name.'" value="'.$$value_name.'">'."\n";
			}
			if ( $$cbracket_name )
			{
				$filter_query .= "&$cbracket_name=".$$cbracket_name;
				$filter_sql .= str_repeat( ")", $$cbracket_name );
				$filter_fields .= '<input type="hidden" name="'.$cbracket_name.'" value="'.$$cbracket_name.'">'."\n";
			}
		}
		if ( $filter_sql )
		{
			$sql .= " and ( $filter_sql )";
		}
		$sql .= " order by $sort_column $sort_order";
		//echo $sql;
		$result = mysql_query( $sql );
		if ( !$result )
		{
			die( mysql_error() );
		}
		$n_rows = mysql_num_rows( $result );

		//echo $filter_query;
?>
<html>
<head>
<title>ZM - <?= $monitor[Name] ?> - Events</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function newWindow(Url,Name,Width,Height)
{
   	var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function eventWindow(Url,Name)
{
	var Name = window.open(Url,Name,"resizable,scrollbars,width=<?= $jws['event']['w'] ?>,height=<?= $jws['event']['h'] ?>");
}
function filterWindow(Url,Name)
{
	var Name = window.open(Url,Name,"resizable,scrollbars,width=<?= $jws['filter']['w'] ?>,height=<?= $jws['filter']['h'] ?>");
}
function closeWindow()
{
	window.close();
	// This is a hack. The only way to close an existing window is to try and open it!
	var filterWindow = window.open( "<?= $PHP_SELF ?>?view=none", 'zmFilter<?= $monitor[Name] ?>', 'width=1,height=1' );
	filterWindow.close();
}
function checkAll(form,name){
	for (var i = 0; i < form.elements.length; i++)
		if (form.elements[i].name.indexOf(name) == 0)
			form.elements[i].checked = 1;
	form.delete_btn.disabled = false;
<?php if ( LEARN_MODE ) { ?>
	form.learn_btn.disabled = false;
	form.learn_state.disabled = false;
<?php } ?>
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
<?php if ( LEARN_MODE ) { ?>
	form.learn_btn.disabled = !checked;
	form.learn_state.disabled = !checked;
<?php } ?>
}
window.focus();
<?php if ( $filter ) { ?>
opener.location.reload(true);
filterWindow( '<?= $PHP_SELF ?>?view=filter&mid=<?= $mid ?><?= $filter_query ?>', 'zmFilter<?= $monitor[Name] ?>' );
location.href = '<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?><?= $filter_query ?>';
<?php } ?>
</script>
</head>
<body>
<form name="event_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="">
<input type="hidden" name="mid" value="<?= $mid ?>">
<?php if ( $filter_fields ) echo $filter_fields ?>
<center><table width="96%" align="center" border="0" cellspacing="1" cellpadding="0">
<tr>
<td valign="top"><table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td align="left" class="text" width="33%"><b><?= $monitor[Name] ?> - <?= $n_rows ?> events</b></td>
<td align="center" class="text" width="34%">&nbsp;</td>
<td align="right" class="text" width="33%"><a href="javascript: closeWindow();">Close</a></td>
</tr>
<tr><td colspan="3" class="text">&nbsp;</td></tr>
<tr>
<td align="right" class="text"><a href="javascript: location.reload(true);">Refresh</td>
<td align="right" class="text"><a href="javascript: filterWindow( '<?= $PHP_SELF ?>?view=filter&mid=<?= $mid ?><?= $filter_query ?>', 'zmFilter<?= $monitor[Name] ?>' );">Show Filter Window</a></td>
<td align="right" class="text"><a href="javascript: checkAll( document.event_form, 'mark_eids' );">Check All</a></td>
</tr>
<tr><td colspan="3" class="text">&nbsp;</td></tr>
<tr><td colspan="3"><table border="0" cellspacing="1" cellpadding="0" width="100%" bgcolor="#7F7FB2">
<?php
		$count = 0;
		while( $row = mysql_fetch_assoc( $result ) )
		{
			if ( ($count++%EVENT_HEADER_LINES) == 0 )
			{
?>
<tr align="center" bgcolor="#FFFFFF">
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?><?= $filter_query ?><?= $sort_parms ?>&sort_field=Id&sort_asc=<?= $sort_field == 'Id'?!$sort_asc:0 ?>">Id<?php if ( $sort_field == "Id" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?><?= $filter_query ?><?= $sort_parms ?>&sort_field=Name&sort_asc=<?= $sort_field == 'Name'?!$sort_asc:0 ?>">Name<?php if ( $sort_field == "Name" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?><?= $filter_query ?><?= $sort_parms ?>&sort_field=Time&sort_asc=<?= $sort_field == 'Time'?!$sort_asc:0 ?>">Time<?php if ( $sort_field == "Time" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?><?= $filter_query ?><?= $sort_parms ?>&sort_field=Secs&sort_asc=<?= $sort_field == 'Secs'?!$sort_asc:0 ?>">Duration<?php if ( $sort_field == "Secs" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?><?= $filter_query ?><?= $sort_parms ?>&sort_field=Frames&sort_asc=<?= $sort_field == 'Frames'?!$sort_asc:0 ?>">Frames<?php if ( $sort_field == "Frames" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?><?= $filter_query ?><?= $sort_parms ?>&sort_field=AlarmFrames&sort_asc=<?= $sort_field == 'AlarmFrames'?!$sort_asc:0 ?>">Alarm<br>Frames<?php if ( $sort_field == "AlarmFrames" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?><?= $filter_query ?><?= $sort_parms ?>&sort_field=TotScore&sort_asc=<?= $sort_field == 'TotScore'?!$sort_asc:0 ?>">Total<br>Score<?php if ( $sort_field == "TotScore" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?><?= $filter_query ?><?= $sort_parms ?>&sort_field=AvgScore&sort_asc=<?= $sort_field == 'AvgScore'?!$sort_asc:0 ?>">Avg.<br>Score<?php if ( $sort_field == "AvgScore" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?><?= $filter_query ?><?= $sort_parms ?>&sort_field=MaxScore&sort_asc=<?= $sort_field == 'MaxScore'?!$sort_asc:0 ?>">Max.<br>Score<?php if ( $sort_field == "MaxScore" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text">Mark</td>
</tr>
<?php
			}
			if ( $row[LearnState] == '+' )
				$bgcolor = "#98FB98";
			elseif ( $row[LearnState] == '-' )
				$bgcolor = "#FFC0CB";
			else
				unset( $bgcolor );
?>
<tr<?= ' bgcolor="'.($bgcolor?$bgcolor:"#FFFFFF").'"' ?> >
<td align="center" class="text"><a href="javascript: eventWindow( '<?= $PHP_SELF ?>?view=event&mid=<?= $mid ?>&eid=<?= $row[Id] ?>', 'zmEvent' );"><span class="<?= $textclass ?>"><?= "$row[Id]" ?><?php if ( $row[Archived] ) echo "*" ?></span></a></td>
<td align="center" class="text"><a href="javascript: eventWindow( '<?= $PHP_SELF ?>?view=event&mid=<?= $mid ?>&eid=<?= $row[Id] ?>', 'zmEvent' );"><span class="<?= $textclass ?>"><?= "$row[Name]" ?><?php if ( $row[Archived] ) echo "*" ?></span></a></td>
<td align="center" class="text"><?= strftime( "%m/%d %H:%M:%S", strtotime($row[StartTime]) ) ?></td>
<td align="center" class="text"><?= $row[Length] ?></td>
<td align="center" class="text"><?= $row[Frames] ?></td>
<td align="center" class="text"><?= $row[AlarmFrames] ?></td>
<td align="center" class="text"><?= $row[TotScore] ?></td>
<td align="center" class="text"><?= $row[AvgScore] ?></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=image&eid=<?= $row[Id] ?>&fid=0', 'zmImage', <?= $monitor[Width]+$jws['image']['w'] ?>, <?= $monitor[Height]+$jws['image']['h'] ?> );"><?= $row[MaxScore] ?></a></td>
<td align="center" class="text"><input type="checkbox" name="mark_eids[]" value="<?= $row[Id] ?>" onClick="configureButton( document.event_form, 'mark_eids' );"></td>
</tr>
<?php
		}
?>
</table></td></tr>
</table></td>
</tr>
<tr><td align="right"><?php if ( LEARN_MODE ) { ?><select name="learn_state" class="form" disabled><option value="">Ignore</option><option value="-">Exclude</option><option value="+">Include</option></select>&nbsp;&nbsp;<input type="button" name="learn_btn" value="Set Learn Prefs" class="form" onClick="document.event_form.action.value = 'learn'; document.event_form.submit();" disabled>&nbsp;&nbsp;<?php } ?><input type="button" name="delete_btn" value="Delete" class="form" onClick="document.event_form.action.value = 'delete'; document.event_form.submit();" disabled></td></tr>
</table></center>
</form>
</body>
</html>
<?php
		break;
	}
	case "filter" :
	{
		$result = mysql_query( "select * from Monitors where Id = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );

		$select_name = "filter_name";
		$filter_names = array( ''=>'Choose Filter' );
		$result = mysql_query( "select * from Filters where MonitorId = '$mid' order by Name" );
		if ( !$result )
			die( mysql_error() );
		while ( $row = mysql_fetch_assoc( $result ) )
		{
			$filter_names[$row[Name]] = $row[Name];
			if ( $filter_name == $row[Name] )
			{
				$filter_data = $row;
			}
		}

		if ( $filter_data )
		{
			//$filter_query = unserialize( $filter_data[Query] );
			//if ( is_array($filter_query) )
			//{
				//while( list( $key, $value ) = each( $filter_query ) )
				//{
					//$$key = $value;
				//}
			//}
			foreach( split( '&', $filter_data[Query] ) as $filter_parm )
			{
				list( $key, $value ) = split( '=', $filter_parm, 2 );
				if ( $key )
				{
					$$key = $value;
				}
			}
		}

		$conjunction_types = array( 'and'=>'and', 'or'=>'or' );
		$obracket_types = array( ''=>'' );
		$cbracket_types = array( ''=>'' );
		for ( $i = 1; $i <= ceil(($trms-1)/2); $i++ )
		{
			$obracket_types[$i] = str_repeat( "(", $i );
			$cbracket_types[$i] = str_repeat( ")", $i );
		}
		$attr_types = array( 'DateTime'=>'Date/Time', 'Date'=>'Date', 'Time'=>'Time', 'Weekday'=>'Weekday', 'Length'=>'Duration', 'Frames'=>'Frames', 'AlarmFrames'=>'Alarm Frames', 'TotScore'=>'Total Score', 'AvgScore'=>'Avg. Score', 'MaxScore'=>'Max. Score', 'Archived'=>'Archive Status' );
		$op_types = array( '='=>'equal to', '!='=>'not equal to', '>='=>'greater than or equal to', '>'=>'greater than', '<'=>'less than', '<='=>'less than or equal to' );
		$archive_types = array( '0'=>'Unarchived Only', '1'=>'Archived Only' );
?>
<html>
<head>
<title>ZM - <?= $monitor[Name] ?> - Event Filter</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function newWindow(Url,Name,Width,Height)
{
   	var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function closeWindow()
{
	top.window.close();
}
function validateForm( form )
{
<?php
		if ( $trms > 2 )
		{
?>
	var bracket_count = 0;
<?php
			for ( $i = 1; $i <= $trms; $i++ )
			{
?>
	bracket_count += form.obr<?= $i ?>.value;
	bracket_count -= form.cbr<?= $i ?>.value;
<?php
			}
?>
	if ( bracket_count )
	{
		alert( "Error, please check you have an equal number of opening and closing brackets" );
		return( false );
	}
<?php
		}
?>
<?php
		for ( $i = 1; $i <= $trms; $i++ )
		{
?>
		if ( form.val<?= $i?>.value == '' )
		{
			alert( "Error, please check that all terms have a valid value" );
			return( false );
		}
<?php
		}
?>
	return( true );
}
function submitToFilter( form )
{
	form.target = window.name;
	form.view.value = 'filter';
	form.submit();
}
function submitToEvents( form )
{
	var Url = '<?= $PHP_SELF ?>';
	var Name = 'zmEvents<?= $monitor[Name] ?>';
	var Width = <?= $jws['events']['w'] ?>;
	var Height = <?= $jws['events']['h'] ?>;
	var Options = 'resizable,scrollbars,width='+Width+',height='+Height;

	window.open( Url, Name, Options );
	form.target = Name;
	form.view.value = 'events';
	form.submit();
}
function saveFilter( form )
{
	var Url = '<?= $PHP_SELF ?>';
	var Name = 'zmEventsFilterSave';
	var Width = <?= $jws['filtersave']['w'] ?>;
	var Height = <?= $jws['filtersave']['h'] ?>;
	var Options = 'resizable,scrollbars,width='+Width+',height='+Height;

	window.open( Url, Name, Options );
	form.target = Name;
	form.view.value = 'filtersave';
	form.submit();
}
function deleteFilter( form, name, id )
{
	if ( confirm( "Delete saved filter '"+name+"'" ) )
	{
		form.action.value = 'delete';
		form.fid.value = name;
		submitToFilter( form );
	}
}
window.focus();
</script>
</head>
<body>
<form name="filter_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="filter">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="action" value="">
<input type="hidden" name="fid" value="">
<center><table width="96%" align="center" border="0" cellspacing="1" cellpadding="0">
<tr>
<td valign="top"><table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td align="left" class="text">Use&nbsp;<select name="trms" class="form" onChange="submitToFilter( document.filter_form );"><?php for ( $i = 0; $i <= 8; $i++ ) { ?><option value="<?= $i ?>"<?php if ( $i == $trms ) { echo " selected"; } ?>><?= $i ?></option><?php } ?></select>&nbsp;filter&nbsp;expressions</td>
<td align="center" class="text">Use filter:&nbsp;<?php if ( count($filter_names) > 1 ) { buildSelect( $select_name, $filter_names, "submitToFilter( document.filter_form );" ); } else { ?><select class="form" disabled><option>No Saved Filters</option></select><?php } ?></td>
<td align="center" class="text"><a href="javascript: saveFilter( document.filter_form );">Save</a></td>
<?php if ( $filter_data ) { ?>
<td align="center" class="text"><a href="javascript: deleteFilter( document.filter_form, '<?= $filter_data[Name] ?>', <?= $filter_data[Id] ?> );">Delete</a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</a></td>
<?php } ?>
<td align="right" class="text"><a href="javascript: closeWindow();">Close</a></td>
</tr>
<tr>
<td colspan="5" class="text">&nbsp;</td>
</tr>
<tr>
<td colspan="5">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<?php
		for ( $i = 1; $i <= $trms; $i++ )
		{
			$conjunction_name = "cnj$i";
			$obracket_name = "obr$i";
			$cbracket_name = "cbr$i";
			$attr_name = "attr$i";
			$op_name = "op$i";
			$value_name = "val$i";
?>
<tr>
<?php
			if ( $i == 1 )
			{
?>
<td class="text">&nbsp;</td>
<?php
			}
			else
			{
?>
<td class="text"><?php buildSelect( $conjunction_name, $conjunction_types ); ?></td>
<?php
			}
?>
<td class="text"><?php if ( $trms > 2 ) { buildSelect( $obracket_name, $obracket_types ); } else { ?>&nbsp;<?php } ?></td>
<td class="text"><?php buildSelect( $attr_name, $attr_types, "$value_name.value = ''; submitToFilter( document.filter_form );" ); ?></td>
<?php if ( $$attr_name == "Archived" ) { ?>
<td class="text"><center>is equal to</center></td>
<td class="text"><?php buildSelect( $value_name, $archive_types ); ?></td>
<?php } elseif ( $$attr_name ) { ?>
<td class="text"><?php buildSelect( $op_name, $op_types ); ?></td>
<td class="text"><input name="<?= $value_name ?>" value="<?= $$value_name ?>" class="form" size="16"></td>
<?php } else { ?>
<td class="text"><?php buildSelect( $op_name, $op_types ); ?></td>
<td class="text"><input name="<?= $value_name ?>" value="<?= $$value_name ?>" class="form" size="16"></td>
<?php } ?>
<td class="text"><?php if ( $trms > 2 ) { buildSelect( $cbracket_name, $cbracket_types ); } else { ?>&nbsp;<?php } ?></td>
</tr>
<?php
		}
?>
</table>
</td>
</tr>
<tr><td colspan="5" class="text">&nbsp;</td></tr>
<tr><td colspan="5" align="right"><input type="reset" value="Reset" class="form">&nbsp;&nbsp;<input type="button" value="Submit" class="form" onClick="if ( validateForm( document.filter_form ) ) submitToEvents( document.filter_form );"></td></tr>
</table></center>
</form>
</body>
</html>
<?php
		break;
	}
	case "filtersave" :
	{
		$result = mysql_query( "select * from Monitors where Id = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );
?>
<html>
<head>
<title>ZM - <?= $monitor[Name] ?> - Save Filter</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function closeWindow()
{
	top.window.close();
}
function validateForm( form )
{
	return( true );
}
window.focus();
</script>
</head>
<body>
<form name="filter_form" method="get" action="<?= $PHP_SELF ?>" onSubmit="validateForm( document.filter_form );">
<input type="hidden" name="view" value="none">
<input type="hidden" name="action" value="filter">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="trms" value="<?= $trms ?>">
<?php
		for ( $i = 1; $i <= $trms; $i++ )
		{
			$conjunction_name = "cnj$i";
			$obracket_name = "obr$i";
			$cbracket_name = "cbr$i";
			$attr_name = "attr$i";
			$op_name = "op$i";
			$value_name = "val$i";
			if ( $i > 1 )
			{
?>
<input type="hidden" name="<?= $conjunction_name ?>" value="<?= $$conjunction_name ?>">
<?php
			}
?>
<input type="hidden" name="<?= $obracket_name ?>" value="<?= $$obracket_name ?>">
<input type="hidden" name="<?= $cbracket_name ?>" value="<?= $$cbracket_name ?>">
<input type="hidden" name="<?= $attr_name ?>" value="<?= $$attr_name ?>">
<input type="hidden" name="<?= $op_name ?>" value="<?= $$op_name ?>">
<input type="hidden" name="<?= $value_name ?>" value="<?= $$value_name ?>">
<?php
		}
?>
<center><table width="96%" align="center" border="0" cellspacing="1" cellpadding="0">
<tr>
<?php
		$select_name = "filter_name";
		$result = mysql_query( "select * from Filters where MonitorId = '$mid' order by Name" );
		if ( !$result )
			die( mysql_error() );
		while ( $row = mysql_fetch_assoc( $result ) )
		{
			$filter_names[$row[Name]] = $row[Name];
			if ( $filter_name == $row[Name] )
			{
				$filter_data = $row;
			}
		}
?>
<?php if ( count($filter_names) ) { ?>
<td align="left" colspan="2" class="text">Save as:&nbsp;<?php buildSelect( $select_name, $filter_names, "submitToFilter( document.filter_form );" ); ?>&nbsp;or enter new name:&nbsp;<input type="text" size="32" name="new_<?= $select_name ?>" value="<?= $filter ?>" class="form"></td>
<?php } else { ?>
<td align="left" colspan="2" class="text">Enter new filter name:&nbsp;<input type="text" size="32" name="new_<?= $select_name ?>" value="" class="form"></td>
<?php } ?>
</tr>
<tr>
<td align="right" colspan="2" class="text">&nbsp;</td>
</tr>
<tr>
<td align="left" class="text">Automatically archive all matching events:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_archive" value="1"<?php if ( $filter_data[AutoArchive] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="left" class="text">Automatically delete all matching events:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_delete" value="1"<?php if ( $filter_data[AutoDelete] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="left" class="text">Automatically upload all matching events:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_upload" value="1"<?php if ( $filter_data[AutoUpload] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="left" class="text">Automatically email details of all matching events:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_email" value="1"<?php if ( $filter_data[AutoEmail] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="left" class="text">Automatically message details of all matching events:&nbsp;</td>
<td align="left" class="text"><input type="checkbox" name="auto_message" value="1"<?php if ( $filter_data[AutoMessage] ) { echo " checked"; } ?>></td>
</tr>
<tr>
<td align="right" colspan="2" class="text">&nbsp;</td>
</tr>
<tr>
<td align="right" colspan="2" class="text"><input type="submit" value="Save" class="form">&nbsp;<input type="button" value="Cancel" class="form" onClick="closeWindow();"></td>
</tr>
</table></center>
</form>
</body>
</html>
<?php
		break;
	}
	case "image" :
	{
		$result = mysql_query( "select E.*,M.Name as MonitorName,M.Width,M.Height from Events as E, Monitors as M where E.Id = '$eid' and E.MonitorId = M.Id" );
		if ( !$result )
			die( mysql_error() );
		$event = mysql_fetch_assoc( $result );

		if ( $fid )
		{
			$result = mysql_query( "select * from Frames where EventID = '$eid' and FrameId = '$fid'" );
			if ( !$result )
				die( mysql_error() );
			$frame = mysql_fetch_assoc( $result );
		}
		else
		{
			$result = mysql_query( "select * from Frames where EventID = '$eid' and Score = '$event[MaxScore]'" );
			if ( !$result )
				die( mysql_error() );
			$frame = mysql_fetch_assoc( $result );
			$fid = $frame[FrameId];
		}

		$result = mysql_query( "select count(*) as FrameCount from Frames where EventID = '$eid'" );
		if ( !$result )
			die( mysql_error() );
		$row = mysql_fetch_assoc( $result );
		$max_fid = $row[FrameCount];

		$first_fid = 1;
		$prev_fid = $fid-1;
		$next_fid = $fid+1;
		$last_fid = $max_fid;

		$image_path = $frame[ImagePath];
		$anal_image = preg_replace( "/capture/", "analyse", $image_path );
		if ( file_exists( $anal_image ) )
		{
			$image_path = $anal_image;
		}
		$img_class = $frame[AlarmFrame]?"alarm":"normal";
?>
<html>
<head>
<title>ZM - Image <?= $eid."-".$fid ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
window.focus();
function newWindow(Url,Name,Width,Height)
{
   	var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function closeWindow()
{
	window.close();
}
function deleteEvent()
{
	location.href = "<?= $PHP_SELF ?>?view=none&action=delete&mark_eid=<?= $eid ?>";
	//window.close();
}
</script>
</head>
<body>
<table border="0">
<tr><td colspan="2" class="smallhead">Image <?= $eid."-".$fid." ($frame[Score])" ?>
<?php if ( ZM_RECORD_EVENT_STATS && $frame[AlarmFrame] ) { ?>
(<a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=stats&eid=<?= $eid ?>&fid=<?= $fid ?>', 'zmStats', <?= $jws['stats']['w'] ?>, <?= $jws['stats']['h'] ?> );">Stats</a>)
<?php } ?>
</td>
<td align="center" class="text"><a href="javascript: deleteEvent();">Delete</a></td>
<td align="right" class="text"><a href="javascript: closeWindow();">Close</a></td>
</tr>
<tr><td colspan="4"><img src="<?= $image_path ?>" width="<?= $event[Width] ?>" height="<?= $event[Height] ?>" class="<?= $img_class ?>"></td></tr>
<tr>
<?php if ( $fid > 1 ) { ?>
<td align="center" width="25%" class="text"><a href="<?= $PHP_SELF ?>?view=image&eid=<?= $eid ?>&fid=<?= $first_fid ?>">First</a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } if ( $fid > 1 ) { ?>
<td align="center" width="25%" class="text"><a href="<?= $PHP_SELF ?>?view=image&eid=<?= $eid ?>&fid=<?= $prev_fid ?>">Prev</a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } if ( $fid < $max_fid ) { ?>
<td align="center" width="25%" class="text"><a href="<?= $PHP_SELF ?>?view=image&eid=<?= $eid ?>&fid=<?= $next_fid ?>">Next</a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } if ( $fid < $max_fid ) { ?>
<td align="center" width="25%" class="text"><a href="<?= $PHP_SELF ?>?view=image&eid=<?= $eid ?>&fid=<?= $last_fid ?>">Last</a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } ?>
</tr>
</table>
</body>
</html>
<?php
		break;
	}
	case "stats" :
	{
		$result = mysql_query( "select S.*,E.*,Z.Name as ZoneName,M.Name as MonitorName,M.Width,M.Height from Stats as S left join Events as E on S.EventId = E.Id left join Zones as Z on S.ZoneId = Z.Id left join Monitors as M on E.MonitorId = M.Id where S.EventId = '$eid' and S.FrameId = '$fid' order by S.ZoneId" );
		if ( !$result )
			die( mysql_error() );
		while ( $row = mysql_fetch_assoc( $result ) )
		{
			$stats[] = $row;
		}
?>
<html>
<head>
<title>ZM - Stats <?= $eid."-".$fid ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
window.focus();
function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table width="96%" border="0">
<tr>
<td align="left" class="smallhead"><b>Image <?= $eid."-".$fid ?></b></td>
<td align="right" class="text"><a href="javascript: closeWindow();">Close</a></td>
</tr>
<tr><td colspan="2"><table width="100%" border="0" bgcolor="#7F7FB2" cellpadding="3" cellspacing="1"><tr bgcolor="#FFFFFF">
<td class="smallhead">Zone</td>
<td class="smallhead" align="right">Alarm Px</td>
<td class="smallhead" align="right">Filter Px</td>
<td class="smallhead" align="right">Blob Px</td>
<td class="smallhead" align="right">Blobs</td>
<td class="smallhead" align="right">Blob Sizes</td>
<td class="smallhead" align="right">Alarm Limits</td>
<td class="smallhead" align="right">Score</td>
</tr>
<?php
	if ( count($stats) )
	{
		foreach ( $stats as $stat )
		{
?>
<tr bgcolor="#FFFFFF">
<td class="text"><?= $stat[ZoneName] ?></td>
<td class="text" align="right"><?= $stat[AlarmPixels] ?></td>
<td class="text" align="right"><?= $stat[FilterPixels] ?></td>
<td class="text" align="right"><?= $stat[BlobPixels] ?></td>
<td class="text" align="right"><?= $stat[Blobs] ?></td>
<td class="text" align="right"><?= $stat[MinBlobSize]."-".$stat[MaxBlobSize] ?></td>
<td class="text" align="right"><?= $stat[MinX].",".$stat[MinY]."-".$stat[MaxX].",".$stat[MaxY] ?></td>
<td class="text" align="right"><?= $stat[Score] ?></td>
</tr>
<?php
		}
	}
	else
	{
?>
<tr bgcolor="#FFFFFF">
<td class="text" colspan="8" align="center"><br>There are no statistics recorded for this event/frame<br><br></td>
</tr>
<?php
	}
?>
</table></td>
</tr>
</table>
</body>
</html>
<?php
		break;
	}
	case "event" :
	{
		if ( !$mode )
		{
			if ( canStream() )
				$mode = "stream";
			else
				$mode = "still";
		}

		$result = mysql_query( "select E.*,M.Name as MonitorName,M.Width,M.Height from Events as E, Monitors as M where E.Id = '$eid' and E.MonitorId = M.Id" );
		if ( !$result )
			die( mysql_error() );
		$event = mysql_fetch_assoc( $result );

		$result = mysql_query( "select * from Events where Id < '$eid' and MonitorId = '$mid' order by Id desc limit 0,1" );
		if ( !$result )
			die( mysql_error() );
		$prev_event = mysql_fetch_assoc( $result );

		$result = mysql_query( "select * from Events where Id > '$eid' and MonitorId = '$mid' order by Id asc limit 0,1" );
		if ( !$result )
			die( mysql_error() );
		$next_event = mysql_fetch_assoc( $result );

?>
<html>
<head>
<title>ZM - Event - <?= $event[Name] ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
<?php
		if ( !$event )
		{
?>
opener.location.reload(true);
window.close();
<?php
		}
?>
window.focus();
<?php
		if ( $refresh_parent )
		{
?>
opener.location.reload(true);
<?php
		}
?>
function refreshWindow()
{
	window.location.reload(true);
}
function closeWindow()
{
	window.close();
}
function newWindow(Url,Name,Width,Height)
{
	var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td colspan="3" align="left" class="text">
<form name="rename_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="rename">
<input type="hidden" name="eid" value="<?= $eid ?>">
<input type="text" size="16" name="event_name" value="<?= $event[Name] ?>" class="form">
<input type="submit" value="Rename" class="form"></form></td>
<td colspan="3" align="right" class="text">
<form name="learn_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="learn">
<input type="hidden" name="eid" value="<?= $eid ?>">
<input type="hidden" name="mark_eid" value="<?= $eid ?>">
<?php if ( LEARN_MODE ) { ?>
Learn Pref:&nbsp;<select name="learn_state" class="form" onChange="learn_form.submit();"><option value=""<?php if ( !$event[LearnState] ) echo " selected" ?>>Ignore</option><option value="-"<?php if ( $event[LearnState]=='-' ) echo " selected" ?>>Exclude</option><option value="+"<?php if ( $event[LearnState]=='+' ) echo " selected" ?>>Include</option></select>
<?php } ?>
</form></td>
</tr>
<tr>
<td align="center" class="text"><a href="javascript: refreshWindow();">Refresh</a></td>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=none&action=delete&mark_eid=<?= $eid ?>">Delete</a></td>
<?php if ( $event[Archived] ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&action=unarchive&eid=<?= $eid ?>">Unarchive</a></td>
<?php } else { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&action=archive&eid=<?= $eid ?>">Archive</a></td>
<?php } ?>
<?php if ( $mode == "stream" ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=event&mode=still&mid=<?= $mid ?>&eid=<?= $eid ?>">Stills</a></td>
<?php } elseif ( canStream() ) { ?>
<td align="center" class="text"><a href="<?= $PHP_SELF ?>?view=event&mode=stream&mid=<?= $mid ?>&eid=<?= $eid ?>">Stream</a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
<?php if ( ZM_OPT_MPEG != "no" ) { ?>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=video&eid=<?= $eid ?>', 'zmVideo', <?= $jws['video']['w']+$event[Width] ?>, <?= $jws['video']['h']+$event[Height] ?> );">Video</a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
<td align="right" class="text"><a href="javascript: closeWindow();">Close</a></td>
</tr>
<?php
		if ( $mode == "stream" )
		{
			$stream_src = ZM_PATH_ZMS."?path=".ZM_PATH_WEB."&event=$eid&refresh=".STREAM_EVENT_DELAY;
			if ( isNetscape() )
			{
?>
<tr><td colspan="6" align="center"><img src="<?= $stream_src ?>" border="0" width="<?= $event[Width] ?>" height="<?= $event[Height] ?>"></td></tr>
<?php
			}
			else
			{
?>
<tr><td colspan="6" align="center"><applet code="com.charliemouse.cambozola.Viewer" archive="<?= ZM_PATH_CAMBOZOLA ?>" align="middle" width="<?= $event[Width] ?>" height="<?= $event[Height] ?>"><param name="url" value="<?= $stream_src ?>"></applet></td></tr>
<?php
			}
		}
		else
		{
			$result = mysql_query( "select * from Frames where EventID = '$eid' order by Id" );
			if ( !$result )
				die( mysql_error() );
?>
<tr><td colspan="6"><table border="0" cellpadding="0" cellspacing="2" align="center">
<tr>
<?php
			$count = 0;
			$scale = IMAGE_SCALING;
			$fraction = sprintf( "%.2f", 1/$scale );
			$thumb_width = $event[Width]/4;
			$thumb_height = $event[Height]/4;
			while( $row = mysql_fetch_assoc( $result ) )
			{
				$frame_id = $row[FrameId];
				$image_path = $row[ImagePath];

				$capt_image = $image_path;
				if ( $scale == 1 || !file_exists( ZM_PATH_NETPBM."/jpegtopnm" ) )
				{
					$anal_image = preg_replace( "/capture/", "analyse", $image_path );

					if ( file_exists($anal_image) && filesize( $anal_image ) )
					{
						$thumb_image = $anal_image;
					}
					else
					{
						$thumb_image = $capt_image;
					}
				}
				else
				{
					$thumb_image = preg_replace( "/capture/", "thumb", $capt_image );

					if ( !file_exists($thumb_image) || !filesize( $thumb_image ) )
					{
						$anal_image = preg_replace( "/capture/", "analyse", $capt_image );
						if ( file_exists( $anal_image ) )
							$command = ZM_PATH_NETPBM."/jpegtopnm -dct fast $anal_image | ".ZM_PATH_NETPBM."/pnmscalefixed $fraction | ".ZM_PATH_NETPBM."/ppmtojpeg --dct=fast > $thumb_image";
						else
							$command = ZM_PATH_NETPBM."/jpegtopnm -dct fast $capt_image | ".ZM_PATH_NETPBM."/pnmscalefixed $fraction | ".ZM_PATH_NETPBM."/ppmtojpeg --dct=fast > $thumb_image";
						#exec( escapeshellcmd( $command ) );
						exec( $command );
					}
				}
				$img_class = $row[AlarmFrame]?"alarm":"normal";
?>
<td align="center" width="88"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=image&eid=<?= $eid ?>&fid=<?= $frame_id ?>', 'zmImage', <?= $event[Width]+$jws['image']['w'] ?>, <?= $event[Height]+$jws['image']['h'] ?> );"><img src="<?= $thumb_image ?>" width="<?= $thumb_width ?>" height="<? echo $thumb_height ?>" class="<?= $img_class ?>" alt="<?= $frame_id ?>/<?= $row[Score] ?>"></a></td>
<?php
				flush();
				if ( !(++$count % 4) )
				{
?>
</tr>
<tr>
<?php
				}
			}
?>
</tr>
</table></td></tr>
<?php
		}
?>
<tr>
<td colspan="6"><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
<td width="25%" align="center" class="text"><?php if ( $prev_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&mid=<?= $mid ?>&eid=<?= $prev_event[Id] ?>">Prev</a><?php } else { ?>&nbsp;<?php } ?></td>
<td width="25%" align="center" class="text"><?php if ( $prev_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&mid=<?= $mid ?>&eid=<?= $prev_event[Id] ?>&action=delete&mark_eid=<?= $eid ?>">Delete & Prev</a><?php } else { ?>&nbsp;<?php } ?></td>
<td width="25%" align="center" class="text"><?php if ( $next_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&mid=<?= $mid ?>&eid=<?= $next_event[Id] ?>&action=delete&mark_eid=<?= $eid ?>">Delete & Next</a><?php } else { ?>&nbsp;<?php } ?></td>
<td width="25%" align="center" class="text"><?php if ( $next_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&mid=<?= $mid ?>&eid=<?= $next_event[Id] ?>">Next</a><?php } else { ?>&nbsp;<?php } ?></td>
</tr></table></td>
</tr>
</table>
</body>
</html>
<?php
		break;
	}
	case "zones" :
	{
		chdir( ZM_DIR_IMAGES );
		$status = exec( escapeshellcmd( ZMU_PATH." -m $mid -z" ) );
		chdir( '..' );

		$result = mysql_query( "select * from Monitors where Id = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );

		$result = mysql_query( "select * from Zones where MonitorId = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$zones = array();
		while( $row = mysql_fetch_assoc( $result ) )
		{
			$zones[] = $row;
		}

		$image = $monitor[Name]."-Zones.jpg";
?>
<html>
<head>
<title>ZM - <?= $monitor[Name] ?> - Zones</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
window.focus();
function newWindow(Url,Name,Width,Height)
{
	var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function closeWindow()
{
	window.close();
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
</script>
</head>
<body>
<map name="zonemap">
<?php
		foreach( $zones as $zone )
		{
			if ( $zone[Units] == 'Percent' )
			{
?>
<area shape="rect" coords="<?= sprintf( "%d,%d,%d,%d", ($zone[LoX]*$monitor[Width])/100, ($zone[LoY]*$monitor[Height])/100, ($zone[HiX]*$monitor[Width])/100, ($zone[HiY]*$monitor[Height])/100 ) ?>" href="javascript: newWindow( '<?= $PHP_SELF ?>?view=zone&mid=<?= $mid ?>&zid=<?= $zone[Id] ?>', 'zmZone', <?= $jws['zone']['w'] ?>, <?= $jws['zone']['h'] ?> );">
<?php
			}
			else
			{
?>
<area shape="rect" coords="<?= "$zone[LoX],$zone[LoY],$zone[HiX],$zone[HiY]" ?>" href="javascript: newWindow( '<?= $PHP_SELF ?>?view=zone&mid=<?= $mid ?>&zid=<?= $zone[Id] ?>', 'zmZone', <?= $jws['zone']['w'] ?>, <?= $jws['zone']['h'] ?> );">
<?php
			}
		}
?>
<area shape="default" nohref>
</map>
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<tr>
<td width="33%" align="left" class="text">&nbsp;</td>
<td width="34%" align="center" class="head"><strong><?= $monitor[Name] ?> Zones</strong></td>
<td width="33%" align="right" class="text"><a href="javascript: closeWindow();">Close</a></td>
</tr>
<tr><td colspan="3" align="center"><img src="<?= ZM_DIR_IMAGES.'/'.$image ?>" usemap="#zonemap" width="<?= $monitor[Width] ?>" height="<?= $monitor[Height] ?>" border="0"></td></tr>
</table>
<table align="center" border="0" cellspacing="0" cellpadding="0" width="96%">
<form name="zone_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="mid" value="<?= $mid ?>">
<tr><td align="center" class="smallhead">Id</td>
<td align="center" class="smallhead">Name</td>
<td align="center" class="smallhead">Type</td>
<td align="center" class="smallhead">Units</td>
<td align="center" class="smallhead">Dimensions</td>
<td align="center" class="smallhead">Mark</td>
</tr>
<?php
		foreach( $zones as $zone )
		{
?>
<tr>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=zone&mid=<?= $mid ?>&zid=<?= $zone[Id] ?>', 'zmZone', <?= $jws['zone']['w'] ?>, <?= $jws['zone']['h'] ?> );"><?= $zone[Id] ?>.</a></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=zone&mid=<?= $mid ?>&zid=<?= $zone[Id] ?>', 'zmZone', <?= $jws['zone']['w'] ?>, <?= $jws['zone']['h'] ?> );"><?= $zone[Name] ?></a></td>
<td align="center" class="text"><?= $zone['Type'] ?></td>
<td align="center" class="text"><?= $zone[Units] ?></td>
<td align="center" class="text"><?= $zone[LoX] ?>,<?= $zone[LoY] ?>-<?= $zone[HiX] ?>,<?= $zone[HiY]?></td>
<td align="center" class="text"><input type="checkbox" name="mark_zids[]" value="<?= $zone[Id] ?>" onClick="configureButton( document.zone_form, 'mark_zids' );"></td>
</tr>
<?php
		}
?>
<tr>
<td align="center" class="text">&nbsp;</td>
<td colspan="4" align="center"><input type="button" value="Add New Zone" class="form" onClick="javascript: newWindow( '<?= $PHP_SELF ?>?view=zone&mid=<?= $mid ?>&zid=-1', 'zmZone', <?= $jws['zone']['w'] ?>, <?= $jws['zone']['h'] ?> );"></td>
<td align="center"><input type="submit" name="delete_btn" value="Delete" class="form" disabled></td>
</tr>
</form>
</table>
</body>
</html>
<?php
		break;
	}
	case "monitor" :
	{
		if ( $mid > 0 )
		{
			$result = mysql_query( "select * from Monitors where Id = '$mid'" );
			if ( !$result )
				die( mysql_error() );
			$monitor = mysql_fetch_assoc( $result );
		}
		else
		{
			$monitor = array();
			$monitor[Name] = "New";
			$monitor['Function'] = "None";
			$monitor[Type] = "Local";
			$monitor[Port] = "80";
			$monitor[Orientation] = "0";
			$monitor[LabelFormat] = '%%s - %y/%m/%d %H:%M:%S';
			$monitor[LabelX] = 0;
			$monitor[LabelY] = 0;
			$monitor[ImageBufferCount] = 100;
			$monitor[WarmupCount] = 25;
			$monitor[PreEventCount] = 10;
			$monitor[PostEventCount] = 10;
			$monitor[MaxFPS] = 0;
			$monitor[FPSReportInterval] = 1000;
			$monitor[RefBlendPerc] = 10;
		}
		$local_palettes = array( "Grey"=>1, "RGB24"=>4, "RGB565"=>3, "YUV420P"=>15 );
		$remote_palettes = array( "8 bit greyscale"=>1, "24 bit colour"=>4 );
		$orientations = array( "Normal"=>0, "Rotate Right"=>90, "Inverted"=>180, "Rotate Left"=>270 );
?>
<html>
<head>
<title>ZM - Monitor <?= $monitor[Name] ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
<?php
		if ( $refresh_parent )
		{
?>
opener.location.reload(true);
<?php
		}
?>
window.focus();
function validateForm(theForm)
{
	return( true );
}

function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td colspan="2" align="left" class="head">Monitor <?= $monitor[Name] ?></td>
</tr>
<form name="monitor_form" method="get" action="<?= $PHP_SELF ?>" onsubmit="return validateForm( document.monitor_form )">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="">
<input type="hidden" name="mid" value="<?= $mid ?>">
<tr>
<td align="left" class="smallhead">Parameter</td><td align="left" class="smallhead">Value</td>
</tr>
<tr><td align="left" class="text">Name</td><td align="left" class="text"><input type="text" name="new_name" value="<?= $monitor[Name] ?>" size="12" class="form"></td></tr>
<tr><td align="left" class="text">Function</td><td align="left" class="text"><select name="new_function" class="form">
<?php
		foreach ( getEnumValues( 'Monitors', 'Function' ) as $opt_function )
		{
			if ( !ZM_OPT_X10 && $opt_function == 'X10' )
				continue;
?>
<option value="<?= $opt_function ?>"<?php if ( $opt_function == $monitor['Function'] ) { ?> selected<?php } ?>><?= $opt_function ?></option>
<?php
		}
?>
</select></td></tr>
<?php
$select_name = "new_type";
$$select_name = $$select_name?$$select_name:$monitor[Type];
$source_types = array( "Local"=>"Local", "Remote"=>"Remote" );
?>
<tr><td align="left" class="text">Source Type</td><td><?php buildSelect( $select_name, $source_types, "document.monitor_form.submit();" ); ?></td></tr>
<?php
		if ( $$select_name == "Local" )
		{
?>
<tr><td align="left" class="text">Device Number (/dev/video?)</td><td align="left" class="text"><input type="text" name="new_device" value="<?= $monitor[Device] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Device Channel</td><td align="left" class="text"><input type="text" name="new_channel" value="<?= $monitor[Channel] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Device Format (0=PAL,1=NTSC etc)</td><td align="left" class="text"><input type="text" name="new_format" value="<?= $monitor[Format] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Capture Palette</td><td align="left" class="text"><select name="new_palette" class="form"><?php foreach ( $local_palettes as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $monitor[Palette] ) { ?> selected<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
		}
		else
		{
?>
<tr><td align="left" class="text">Remote Host Name</td><td align="left" class="text"><input type="text" name="new_host" value="<?= $monitor[Host] ?>" size="16" class="form"></td></tr>
<tr><td align="left" class="text">Remote Host Port</td><td align="left" class="text"><input type="text" name="new_port" value="<?= $monitor[Port] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text">Remote Host Path</td><td align="left" class="text"><input type="text" name="new_path" value="<?= $monitor[Path] ?>" size="36" class="form"></td></tr>
<tr><td align="left" class="text">Remote Image Colours</td><td align="left" class="text"><select name="new_palette" class="form"><?php foreach ( $remote_palettes as $name => $value ) { ?><option value= <?= $value ?>"<?php if ( $value == $monitor[Palette] ) { ?> selected<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<?php
		}
?>
<tr><td align="left" class="text">Capture Width (pixels)</td><td align="left" class="text"><input type="text" name="new_width" value="<?= $monitor[Width] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Capture Height (pixels)</td><td align="left" class="text"><input type="text" name="new_height" value="<?= $monitor[Height] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Orientation</td><td align="left" class="text"><select name="new_orientation" class="form"><?php foreach ( $orientations as $name => $value ) { ?><option value="<?= $value ?>"<?php if ( $value == $monitor[Orientation] ) { ?> selected<?php } ?>><?= $name ?></option><?php } ?></select></td></tr>
<tr><td align="left" class="text">Timestamp Label Format</td><td align="left" class="text"><input type="text" name="new_label_format" value="<?= $monitor[LabelFormat] ?>" size="20" class="form"></td></tr>
<tr><td align="left" class="text">Timestamp Label X</td><td align="left" class="text"><input type="text" name="new_label_x" value="<?= $monitor[LabelX] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Timestamp Label Y</td><td align="left" class="text"><input type="text" name="new_label_y" value="<?= $monitor[LabelY] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Image Buffer Size (frames)</td><td align="left" class="text"><input type="text" name="new_image_buffer_count" value="<?= $monitor[ImageBufferCount] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Warmup Frames</td><td align="left" class="text"><input type="text" name="new_warmup_count" value="<?= $monitor[WarmupCount] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Pre Event Image Buffer</td><td align="left" class="text"><input type="text" name="new_pre_event_count" value="<?= $monitor[PreEventCount] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Post Event Image Buffer</td><td align="left" class="text"><input type="text" name="new_post_event_count" value="<?= $monitor[PostEventCount] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Maximum FPS</td><td align="left" class="text"><input type="text" name="new_max_fps" value="<?= $monitor[MaxFPS] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">FPS Report Interval</td><td align="left" class="text"><input type="text" name="new_fps_report_interval" value="<?= $monitor[FPSReportInterval] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Reference Image Blend %ge</td><td align="left" class="text"><input type="text" name="new_ref_blend_perc" value="<?= $monitor[RefBlendPerc] ?>" size="4" class="form"></td></tr>
<?php if ( ZM_OPT_X10 ) { ?>
<tr><td align="left" class="text">X10 Activation String</td><td align="left" class="text"><input type="text" name="new_x10_activation" value="<?= $monitor[X10Activation] ?>" size="20" class="form"></td></tr>
<tr><td align="left" class="text">X10 Input Alarm String</td><td align="left" class="text"><input type="text" name="new_x10_alarm_input" value="<?= $monitor[X10AlarmInput] ?>" size="20" class="form"></td></tr>
<tr><td align="left" class="text">X10 Output Alarm String</td><td align="left" class="text"><input type="text" name="new_x10_alarm_output" value="<?= $monitor[X10AlarmOutput] ?>" size="20" class="form"></td></tr>
<?php } ?>
<tr><td colspan="2" align="left" class="text">&nbsp;</td></tr>
<tr>
<td align="left"><input type="submit" value="Save" class="form" onClick="document.monitor_form.view.value='none'; document.monitor_form.action.value='monitor';"></td>
<td align="left"><input type="button" value="Cancel" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
<?php
		break;
	}
	case "zone" :
	{
		$result = mysql_query( "select * from Monitors where Id = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );

		if ( $zid > 0 )
		{
			$result = mysql_query( "select * from Zones where MonitorId = '$mid' and Id = '$zid'" );
			if ( !$result )
				die( mysql_error() );
			$zone = mysql_fetch_assoc( $result );
		}
		else
		{
			$zone = array();
			$zone[Name] = "New";
			$zone[LoX] = 0;
			$zone[LoY] = 0;
			$zone[HiX] = $monitor[Width]-1;
			$zone[HiY] = $monitor[Height]-1;
		}
?>
<html>
<head>
<title>ZM - <?= $monitor[Name] ?> - Zone <?= $zone[Name] ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
<?php
		if ( $refresh_parent )
		{
?>
opener.location.reload(true);
<?php
		}
?>
window.focus();
function validateForm(theForm)
{
	theForm.new_alarm_rgb.value = (theForm.new_alarm_rgb_r.value<<16)|(theForm.new_alarm_rgb_g.value<<8)|theForm.new_alarm_rgb_b.value;
	return( true );
}

function applyZoneType(theForm)
{
	if ( theForm.new_type.value == 'Inactive' )
	{
		theForm.new_alarm_rgb_r.disabled = true;
		theForm.new_alarm_rgb_r.value = "";
		theForm.new_alarm_rgb_g.disabled = true;
		theForm.new_alarm_rgb_g.value = "";
		theForm.new_alarm_rgb_b.disabled = true;
		theForm.new_alarm_rgb_b.value = "";
		theForm.new_alarm_threshold.disabled = true;
		theForm.new_alarm_threshold.value = "";
		theForm.new_min_alarm_pixels.disabled = true;
		theForm.new_min_alarm_pixels.value = "";
		theForm.new_max_alarm_pixels.disabled = true;
		theForm.new_max_alarm_pixels.value = "";
		theForm.new_filter_x.disabled = true;
		theForm.new_filter_x.value = "";
		theForm.new_filter_y.disabled = true;
		theForm.new_filter_y.value = "";
		theForm.new_min_filter_pixels.disabled = true;
		theForm.new_min_filter_pixels.value = "";
		theForm.new_max_filter_pixels.disabled = true;
		theForm.new_max_filter_pixels.value = "";
		theForm.new_min_blob_pixels.disabled = true;
		theForm.new_min_blob_pixels.value = "";
		theForm.new_max_blob_pixels.disabled = true;
		theForm.new_max_blob_pixels.value = "";
		theForm.new_min_blobs.disabled = true;
		theForm.new_min_blobs.value = "";
		theForm.new_max_blobs.disabled = true;
		theForm.new_max_blobs.value = "";
	}
	else if ( theForm.new_type.value == 'Preclusive' )
	{
		theForm.new_alarm_rgb_r.disabled = true;
		theForm.new_alarm_rgb_r.value = "";
		theForm.new_alarm_rgb_g.disabled = true;
		theForm.new_alarm_rgb_g.value = "";
		theForm.new_alarm_rgb_b.disabled = true;
		theForm.new_alarm_rgb_b.value = "";
		theForm.new_alarm_threshold.disabled = false;
		theForm.new_alarm_threshold.value = "<?= $zone[AlarmThreshold] ?>";
		theForm.new_min_alarm_pixels.disabled = false;
		theForm.new_min_alarm_pixels.value = "<?= $zone[MinAlarmPixels] ?>";
		theForm.new_max_alarm_pixels.disabled = false;
		theForm.new_max_alarm_pixels.value = "<?= $zone[MaxAlarmPixels] ?>";
		theForm.new_filter_x.disabled = false;
		theForm.new_filter_x.value = "<?= $zone[FilterX] ?>";
		theForm.new_filter_y.disabled = false;
		theForm.new_filter_y.value = "<?= $zone[FilterY] ?>";
		theForm.new_min_filter_pixels.disabled = false;
		theForm.new_min_filter_pixels.value = "<?= $zone[MinFilterPixels] ?>";
		theForm.new_max_filter_pixels.disabled = false;
		theForm.new_max_filter_pixels.value = "<?= $zone[MaxFilterPixels] ?>";
		theForm.new_min_blob_pixels.disabled = false;
		theForm.new_min_blob_pixels.value = "<?= $zone[MinBlobPixels] ?>";
		theForm.new_max_blob_pixels.disabled = false;
		theForm.new_max_blob_pixels.value = "<?= $zone[MaxBlobPixels] ?>";
		theForm.new_min_blobs.disabled = false;
		theForm.new_min_blobs.value = "<?= $zone[MinBlobs] ?>";
		theForm.new_max_blobs.disabled = false;
		theForm.new_max_blobs.value = "<?= $zone[MaxBlobs] ?>";
	}
	else
	{
		theForm.new_alarm_rgb_r.disabled = false;
		theForm.new_alarm_rgb_r.value = "<?= ($zone[AlarmRGB]>>16)&0xff; ?>";
		theForm.new_alarm_rgb_g.disabled = false;
		theForm.new_alarm_rgb_g.value = "<?= ($zone[AlarmRGB]>>8)&0xff; ?>";
		theForm.new_alarm_rgb_b.disabled = false;
		theForm.new_alarm_rgb_b.value = "<?= $zone[AlarmRGB]&0xff; ?>";
		theForm.new_alarm_threshold.disabled = false;
		theForm.new_alarm_threshold.value = "<?= $zone[AlarmThreshold] ?>";
		theForm.new_min_alarm_pixels.disabled = false;
		theForm.new_min_alarm_pixels.value = "<?= $zone[MinAlarmPixels] ?>";
		theForm.new_max_alarm_pixels.disabled = false;
		theForm.new_max_alarm_pixels.value = "<?= $zone[MaxAlarmPixels] ?>";
		theForm.new_filter_x.disabled = false;
		theForm.new_filter_x.value = "<?= $zone[FilterX] ?>";
		theForm.new_filter_y.disabled = false;
		theForm.new_filter_y.value = "<?= $zone[FilterY] ?>";
		theForm.new_min_filter_pixels.disabled = false;
		theForm.new_min_filter_pixels.value = "<?= $zone[MinFilterPixels] ?>";
		theForm.new_max_filter_pixels.disabled = false;
		theForm.new_max_filter_pixels.value = "<?= $zone[MaxFilterPixels] ?>";
		theForm.new_min_blob_pixels.disabled = false;
		theForm.new_min_blob_pixels.value = "<?= $zone[MinBlobPixels] ?>";
		theForm.new_max_blob_pixels.disabled = false;
		theForm.new_max_blob_pixels.value = "<?= $zone[MaxBlobPixels] ?>";
		theForm.new_min_blobs.disabled = false;
		theForm.new_min_blobs.value = "<?= $zone[MinBlobs] ?>";
		theForm.new_max_blobs.disabled = false;
		theForm.new_max_blobs.value = "<?= $zone[MaxBlobs] ?>";
	}
}

function toPixels(theField,maxValue)
{
		theField.value = Math.round((theField.value*maxValue)/100);
}

function toPercent(theField,maxValue)
{
		theField.value = Math.round((100*theField.value)/maxValue);
}

function applyZoneUnits(theForm)
{
	var max_width = <?= $monitor[Width]-1 ?>;
	var max_height = <?= $monitor[Height]-1 ?>;
	var area = (max_width+1) * (max_height+1);

	if ( theForm.new_units.value == 'Pixels' )
	{
		toPixels( theForm.new_lo_x, max_width );
		toPixels( theForm.new_lo_y, max_height );
		toPixels( theForm.new_hi_x, max_width );
		toPixels( theForm.new_hi_y, max_height );
		toPixels( theForm.new_min_alarm_pixels, area );
		toPixels( theForm.new_max_alarm_pixels, area );
		toPixels( theForm.new_min_filter_pixels, area );
		toPixels( theForm.new_max_filter_pixels, area );
		toPixels( theForm.new_min_blob_pixels, area );
		toPixels( theForm.new_max_blob_pixels, area );
	}
	else
	{
		toPercent( theForm.new_lo_x, max_width );
		toPercent( theForm.new_lo_y, max_height );
		toPercent( theForm.new_hi_x, max_width );
		toPercent( theForm.new_hi_y, max_height );
		toPercent( theForm.new_min_alarm_pixels, area );
		toPercent( theForm.new_max_alarm_pixels, area );
		toPercent( theForm.new_min_filter_pixels, area );
		toPercent( theForm.new_max_filter_pixels, area );
		toPercent( theForm.new_min_blob_pixels, area );
		toPercent( theForm.new_max_blob_pixels, area );
	}
}

function checkBounds(theField,fieldText,minValue,maxValue)
{
	if ( document.zone_form.new_units.value == "Percent" )
	{
		minValue = 0;
		maxValue = 100;
	}
	if ( theField.value < minValue )
	{
		alert( fieldText + " must be greater than or equal to " + minValue );
		theField.value = minValue;
	}
	if ( theField.value > maxValue )
	{
		alert( fieldText + " must be less than or equal to " + maxValue );
		theField.value = maxValue;
	}
}

function checkWidth(theField,fieldText)
{
	return( checkBounds( theField, fieldText, 0, <?= $monitor[Width]-1 ?> ) );
}

function checkHeight(theField,fieldText)
{
	return( checkBounds( theField, fieldText, 0, <?= $monitor[Height]-1 ?> ) );
}

function checkArea(theField,fieldText)
{
	return( checkBounds( theField, fieldText, 0, <?= $monitor[Width]*$monitor[Height] ?> ) );
}

function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td colspan="2" align="left" class="head">Monitor <?= $monitor[Name] ?> - Zone <?= $zone[Name] ?></td>
</tr>
<form name="zone_form" method="get" action="<?= $PHP_SELF ?>" onsubmit="return validateForm( document.zone_form )">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="zone">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="zid" value="<?= $zid ?>">
<input type="hidden" name="new_alarm_rgb" value="">
<tr>
<td align="left" class="smallhead">Parameter</td><td align="left" class="smallhead">Value</td>
</tr>
<tr><td align="left" class="text">Name</td><td align="left" class="text"><input type="text" name="new_name" value="<?= $zone[Name] ?>" size="12" class="form"></td></tr>
<tr><td align="left" class="text">Type</td><td align="left" class="text"><select name="new_type" class="form" onchange="applyZoneType(document.zone_form)">
<?php
		foreach ( getEnumValues( 'Zones', 'Type' ) as $opt_type )
		{
?>
<option value="<?= $opt_type ?>"<?php if ( $opt_type == $zone['Type'] ) { ?> selected<?php } ?>><?= $opt_type ?></option>
<?php
		}
?>
</select></td></tr>
<tr><td align="left" class="text">Units</td><td align="left" class="text"><select name="new_units" class="form" onchange="applyZoneUnits(document.zone_form)">
<?php
		foreach ( getEnumValues( 'Zones', 'Units' ) as $opt_units )
		{
?>
<option value="<?= $opt_units ?>"<?php if ( $opt_units == $zone['Units'] ) { ?> selected<?php } ?>><?= $opt_units ?></option>
<?php
		}
?>
</select></td></tr>
<tr><td align="left" class="text">Minimum X (left)</td><td align="left" class="text"><input type="text" name="new_lo_x" value="<?= $zone[LoX] ?>" size="4" class="form" onchange="checkWidth(this,'Minimum X')"></td></tr>
<tr><td align="left" class="text">Minimum Y (top)</td><td align="left" class="text"><input type="text" name="new_lo_y" value="<?= $zone[LoY] ?>" size="4" class="form" onchange="checkHeight(this,'Minimum Y')"></td></tr>
<tr><td align="left" class="text">Maximum X (right)</td><td align="left" class="text"><input type="text" name="new_hi_x" value="<?= $zone[HiX] ?>" size="4" class="form" onchange="checkWidth(this,'Maximum X')"></td></tr>
<tr><td align="left" class="text">Maximum Y (bottom)</td><td align="left" class="text"><input type="text" name="new_hi_y" value="<?= $zone[HiY] ?>" size="4" class="form" onchange="checkHeight(this,'Maximum Y')"></td></tr>
<tr><td align="left" class="text">Alarm Colour (RGB)</td><td align="left" class="text">R:<input type="text" name="new_alarm_rgb_r" value="<?= ($zone[AlarmRGB]>>16)&0xff ?>" size="3" class="form">&nbsp;G:<input type="text" name="new_alarm_rgb_g" value="<?= ($zone[AlarmRGB]>>8)&0xff ?>" size="3" class="form">&nbsp;B:<input type="text" name="new_alarm_rgb_b" value="<?= $zone[AlarmRGB]&0xff ?>" size="3" class="form"></td></tr>
<tr><td align="left" class="text">Alarm Threshold (0>=?<=255)</td><td align="left" class="text"><input type="text" name="new_alarm_threshold" value="<?= $zone[AlarmThreshold] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Minimum Alarmed Area</td><td align="left" class="text"><input type="text" name="new_min_alarm_pixels" value="<?= $zone[MinAlarmPixels] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Alarmed Area')"></td></tr>
<tr><td align="left" class="text">Maximum Alarmed Area</td><td align="left" class="text"><input type="text" name="new_max_alarm_pixels" value="<?= $zone[MaxAlarmPixels] ?>" size="6" class="form" onchange="checkArea(this,'Maximum Alarmed Area')"></td></tr>
<tr><td align="left" class="text">Filter Width (pixels)</td><td align="left" class="text"><input type="text" name="new_filter_x" value="<?= $zone[FilterX] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Filter Height (pixels)</td><td align="left" class="text"><input type="text" name="new_filter_y" value="<?= $zone[FilterY] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Minimum Filtered Area</td><td align="left" class="text"><input type="text" name="new_min_filter_pixels" value="<?= $zone[MinFilterPixels] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Filtered Area')"></td></tr>
<tr><td align="left" class="text">Maximum Filtered Area</td><td align="left" class="text"><input type="text" name="new_max_filter_pixels" value="<?= $zone[MaxFilterPixels] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Filtered Area')"></td></tr>
<tr><td align="left" class="text">Minimum Blob Area</td><td align="left" class="text"><input type="text" name="new_min_blob_pixels" value="<?= $zone[MinBlobPixels] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text">Maximum Blob Area</td><td align="left" class="text"><input type="text" name="new_max_blob_pixels" value="<?= $zone[MaxBlobPixels] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text">Minimum Blobs</td><td align="left" class="text"><input type="text" name="new_min_blobs" value="<?= $zone[MinBlobs] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Maximum Blobs</td><td align="left" class="text"><input type="text" name="new_max_blobs" value="<?= $zone[MaxBlobs] ?>" size="4" class="form"></td></tr>
<tr><td colspan="2" align="left" class="text">&nbsp;</td></tr>
<tr>
<td align="left"><input type="submit" value="Save" class="form"></td>
<td align="left"><input type="button" value="Cancel" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
<?php
		break;
	}
	case "video" :
	{
		$result = mysql_query( "select E.*,M.Name as MonitorName, M.Palette from Events as E, Monitors as M where E.Id = '$eid' and E.MonitorId = M.Id" );
		if ( !$result )
			die( mysql_error() );
		$event = mysql_fetch_assoc( $result );

		ob_start();

		// Note this all has a bunch of extra padding as IE won't flush less than 1024 chars
?>
<html>
<head>
<title>ZM - Video - <?= $event[Name] ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
<tr>
<td align="center" class="head">Generating Video</td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
</table>
</body>
<?php
		$buffer_string = "<!-- This is some long buffer text to ensure that IE flushes correctly -->";
		for ( $i = 0; $i < 4096/strlen($buffer_string); $i++ )
		{
			echo $buffer_string."\n";
		}
?>
</html>
<?php
		ob_end_flush();
		if ( $video_file = createVideo( $event ) )
		{
			$event_dir = ZM_DIR_EVENTS."/$event[MonitorName]/".sprintf( "%d", $eid );
			$video_path = $event_dir.'/'.$video_file;
			//header("Location: $video_path" );
?>
<html>
<head>
<title>ZM - Video - <?= $event[Name] ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
location.replace('<?= $video_path ?>');
</script>
</head>
</html>
<?php
		}
		else
		{
?>
<html>
<head>
<title>ZM - Video - <?= $event[Name] ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
</head>
<body>
<p class="head" align="center"><font color="red"><br><br><br>Video Generation Failed!<br><br><br></font></p>
</body>
</html>
<?php
		}
		break;
	}
	case "function" :
	{
		$result = mysql_query( "select * from Monitors where Id = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );
?>
<html>
<head>
<title>ZM - Function - <?= $monitor[Name] ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
<?php
	if ( $refresh_parent )
	{
?>
opener.location.reload(true);
<?php
	}
?>
window.focus();
function refreshWindow()
{
	window.location.reload(true);
}
function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td colspan="2" align="center" class="head">Monitor '<?= $monitor[Name] ?>' Function</td>
</tr>
<tr>
<form method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="none">
<input type="hidden" name="action" value="function">
<input type="hidden" name="mid" value="<?= $mid ?>">
<td colspan="2" align="center"><select name="new_function" class="form">
<?php
		foreach ( getEnumValues( 'Monitors', 'Function' ) as $opt_function )
		{
			if ( !ZM_OPT_X10 && $opt_function == 'X10' )
				continue;
?>
<option value="<?= $opt_function ?>"<?php if ( $opt_function == $monitor['Function'] ) { ?> selected<?php } ?>><?= $opt_function ?></option>
<?php
		}
?>
</select></td>
</tr>
<tr>
<td align="center"><input type="submit" value="Save" class="form"></td>
<td align="center"><input type="button" value="Cancel" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
<?php
		break;
	}
	case "none" :
	{
?>
<html>
<head>
<script language="JavaScript">
<?php
		if ( $refresh_parent )
		{
?>
//self.onerror = function() { return( true ); }
opener.location.reload(true);
<?php
		}
?>
window.close();
</script>
</head>
</html>
<?php
		break;
	}
}
?>
<?php

function buildSelect( $name, $contents, $onchange="" )
{
	global $$name;
?>
<select name="<?= $name ?>" class="form"<?php if ( $onchange ) { echo " onChange=\"$onchange\""; } ?>>
<?php
	foreach ( $contents as $content_value => $content_text )
	{
?>
<option value="<?= $content_value ?>"<?php if ( $$name == $content_value ) { echo " selected"; } ?>><?= $content_text ?></option>
<?php
	}
?>
</select>
<?php
}
?>
