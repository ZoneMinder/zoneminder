<?php

// Zone Monitor web interface file, $Date$, $Revision$

import_request_variables( "GPC" );

//phpinfo( INFO_VARIABLES );

$PHP_SELF = $_SERVER['PHP_SELF'];

if ( !$bandwidth )
{
	$new_bandwidth = "low";
}

if ( $new_bandwidth )
{
	$bandwidth = $new_bandwidth;
	setcookie( "bandwidth", $new_bandwidth, time()+3600*24*30*12*10 );
}

define( "DB_SERVER", "localhost" );	// Database Server machine
define( "DB_NAME", "zm" );			// Database containing the tables
define( "DB_USER", "zmadmin" );		// Database login
define( "DB_PASS", "zmadminzm" );	// Database password

define( "MAX_EVENTS", 12 );
define( "ZM_PATH", "/usr/local/bin" );
define( "ZMU_PATH", ZM_PATH."/zmu" );
define( "ZMS_PATH", "/cgi-bin/zms" );
define( "ZMS_EVENT_PATH", "/data/zm" );
define( "CAMBOZOLA_PATH", "cambozola.jar" );

if ( $bandwidth == "high" )
{
	define( "REFRESH_MAIN", 300 );
	define( "REFRESH_CYCLE", 5 );
	define( "REFRESH_IMAGE", 5 );
	define( "REFRESH_STATUS", 3 );
	define( "REFRESH_EVENTS", 30 );
	define( "REFRESH_EVENTS_ALL", 120 );
	define( "STREAM_IDLE_DELAY", 1000 );
	define( "STREAM_FRAME_DELAY", 50 );
	define( "STREAM_EVENT_DELAY", 200 );
	define( "IMAGE_SCALING", 1 );
}
elseif ( $bandwidth == "medium" )
{
	define( "REFRESH_MAIN", 300 );
	define( "REFRESH_CYCLE", 10 );
	define( "REFRESH_IMAGE", 15 );
	define( "REFRESH_STATUS", 5 );
	define( "REFRESH_EVENTS", 60 );
	define( "REFRESH_EVENTS_ALL", 300 );
	define( "STREAM_IDLE_DELAY", 5000 );
	define( "STREAM_FRAME_DELAY", 100 );
	define( "STREAM_EVENT_DELAY", 50 );
	define( "IMAGE_SCALING", 2 );
}
else
{
	define( "REFRESH_MAIN", 300 );
	define( "REFRESH_CYCLE", 30 );
	define( "REFRESH_IMAGE", 30 );
	define( "REFRESH_STATUS", 10 );
	define( "REFRESH_EVENTS", 180 );
	define( "REFRESH_EVENTS_ALL", 600 );
	define( "STREAM_IDLE_DELAY", 10000 );
	define( "STREAM_FRAME_DELAY", 250 );
	define( "STREAM_EVENT_DELAY", 10 );
	define( "IMAGE_SCALING", 4 );
}

$conn = mysql_connect( DB_SERVER, DB_USER, DB_PASS ) or die("Could not connect to database: ".mysql_error());
mysql_select_db( DB_NAME, $conn) or die("Could not select database: ".mysql_error());

if ( $action )
{
	if ( $action == "rename" && $event_name && $eid )
	{
		$result = mysql_query( "update Events set Name = '$event_name' where Id = '$eid'" );
		if ( !$result )
			die( mysql_error() );
	}
	elseif ( $action == "archive" && $eid )
	{
		$result = mysql_query( "update Events set Archived = 1 where Id = '$eid'" );
		if ( !$result )
			die( mysql_error() );
	}
	elseif ( $action == "delete" && $delete_eids )
	{
		foreach( $delete_eids as $delete_eid )
		{
			$result = mysql_query( "delete from Frames where EventId = '$delete_eid'" );
			if ( !$result )
				die( mysql_error() );
			$result = mysql_query( "delete from Events where Id = '$delete_eid'" );
			if ( !$result )
				die( mysql_error() );
			if ( $delete_eid )
				system( escapeshellcmd( "rm -rf events/*/".sprintf( "%04d", $delete_eid ) ) );
		}
	}
	elseif ( $action == "function" && $mid )
	{
		$sql = "select * from Monitors where Id = '$mid'";
		$result = mysql_query( $sql );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );

		$old_function = $monitor['Function'];
		if ( $new_function != $old_function )
		{
			$sql = "update Monitors set Function = '$new_function' where Id = '$mid'";
			$result = mysql_query( $sql );
			if ( !$result )
				echo mysql_error();
			$sql = "select count(if(Function='Passive',1,NULL)) as PassiveCount, count(if(Function='Active',1,NULL)) as ActiveCount from Monitors where Id = '$mid'";
			$result = mysql_query( $sql );
			if ( !$result )
				echo mysql_error();
			$row = mysql_fetch_assoc( $result );
			$passive_count = $row[PassiveCount];
			$active_count = $row[ActiveCount];

			if ( !$passive_count && !$active_count )
			{
				stopDaemon( "zmc", $monitor[Device] );
			}
			else
			{
				startDaemon( "zmc", $monitor[Device] );
			}
			if ( !$active_count )
			{
				stopDaemon( "zma", $monitor[Device] );
			}
			else
			{
				startDaemon( "zma", $monitor[Device] );
			}
			$refresh_parent = true;
		}
	}
	elseif ( $action == "device" && isset( $did ) )
	{
		if ( $zmc_status && !$zmc_action )
		{
			stopDaemon( "zmc", $did );
		}
		elseif ( !$zmc_status && $zmc_action )
		{
			startDaemon( "zmc", $did );
		}
		if ( $zma_status && !$zma_action )
		{
			stopDaemon( "zma", $did );
		}
		elseif ( !$zma_status && $zma_action )
		{
			startDaemon( "zma", $did );
		}
	}
	elseif ( $action == "zone" && isset( $mid ) && isset( $zid ) )
	{
		$result = mysql_query( "select * from Monitors where Id = '$mid'" );
		if ( !$result )
			die( mysql_error() );
		$monitor = mysql_fetch_assoc( $result );

		$result = mysql_query( "select * from Zones where MonitorId = '$mid' and Id = '$zid'" );
		if ( !$result )
			die( mysql_error() );
		$zone = mysql_fetch_assoc( $result );

		$changes = array();
		if ( $new_name != $zone[Name] ) $changes[] = "Name = '$new_name'";
		if ( $new_type != $zone['Type'] ) $changes[] = "Type = '$new_type'";
		if ( $new_units != $zone[Units] ) $changes[] = "Units = '$new_units'";
		if ( $new_lo_x != $zone[LoX] ) $changes[] = "LoX = '$new_lo_x'";
		if ( $new_lo_y != $zone[LoY] ) $changes[] = "LoY = '$new_lo_y'";
		if ( $new_hi_x != $zone[HiX] ) $changes[] = "HiX = '$new_hi_x'";
		if ( $new_hi_y != $zone[HiY] ) $changes[] = "HiY = '$new_hi_y'";
		if ( $new_alarm_rgb != $zone[AlarmRGB] ) $changes[] = "AlarmRGB = '$new_alarm_rgb'";
		if ( $new_alarm_threshold != $zone[AlarmThreshold] ) $changes[] = "AlarmThreshold = '$new_alarm_threshold'";
		if ( $new_min_alarm_pixels != $zone[MinAlarmPixels] ) $changes[] = "MinAlarmPixels = '$new_min_alarm_pixels'";
		if ( $new_max_alarm_pixels != $zone[MaxAlarmPixels] ) $changes[] = "MaxAlarmPixels = '$new_max_alarm_pixels'";
		if ( $new_filter_x != $zone[FilterX] ) $changes[] = "FilterX = '$new_filter_x'";
		if ( $new_filter_y != $zone[FilterY] ) $changes[] = "FilterY = '$new_filter_y'";
		if ( $new_min_filter_pixels != $zone[MinFilterPixels] ) $changes[] = "MinFilterPixels = '$new_min_filter_pixels'";
		if ( $new_max_filter_pixels != $zone[MaxFilterPixels] ) $changes[] = "MaxFilterPixels = '$new_max_filter_pixels'";
		if ( $new_min_blob_pixels != $zone[MinBlobPixels] ) $changes[] = "MinBlobPixels = '$new_min_blob_pixels'";
		if ( $new_max_blob_pixels != $zone[MaxBlobPixels] ) $changes[] = "MaxBlobPixels = '$new_max_blob_pixels'";
		if ( $new_min_blobs != $zone[MinBlobs] ) $changes[] = "MinBlobs = '$new_min_blobs'";
		if ( $new_max_blobs != $zone[MaxBlobs] ) $changes[] = "MaxBlobs = '$new_max_blobs'";

		if ( count( $changes ) )
		{
			$sql = "update Zones set ".implode( ", ", $changes )." where MonitorId = '$mid' and Id = '$zid'";
			#echo "<html>$sql</html>";
			$result = mysql_query( $sql );
			if ( !$result )
				die( mysql_error() );
			startDaemon( "zma", $monitor[Device] );
			$refresh_parent = true;
		}
	}
}

if ( !$view )
{
	$view = "console";
}

if ( $view == "console" )
{
	header("Refresh: ".REFRESH_MAIN."; URL='$PHP_SELF'" );
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
	header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");                          // HTTP/1.0

	//$result = mysql_query( "select M.*, count(E.Id) as EventCount, count(if(E.Archived,1,NULL)) as ArchEventCount, count(if(E.StartTime>NOW() - INTERVAL 1 HOUR && E.Archived = 0,1,NULL)) as HourEventCount, count(if(E.StartTime>NOW() - INTERVAL 1 DAY && E.Archived = 0,1,NULL)) as DayEventCount, count(if(E.StartTime>NOW() - INTERVAL 7 DAY && E.Archived = 0,1,NULL)) as WeekEventCount, count(if(E.StartTime>NOW() - INTERVAL 1 MONTH && E.Archived = 0,1,NULL)) as MonthEventCount, count(Z.MonitorId) as ZoneCount from Monitors as M inner join Zones as Z on Z.MonitorId = M.Id left join Events as E on E.MonitorId = M.Id group by E.MonitorId,Z.MonitorId order by Id" );
	$sql = "select M.*, count(E.Id) as EventCount, count(if(E.Archived,1,NULL)) as ArchEventCount, count(if(E.StartTime>NOW() - INTERVAL 1 HOUR && E.Archived = 0,1,NULL)) as HourEventCount, count(if(E.StartTime>NOW() - INTERVAL 1 DAY && E.Archived = 0,1,NULL)) as DayEventCount, count(if(E.StartTime>NOW() - INTERVAL 7 DAY && E.Archived = 0,1,NULL)) as WeekEventCount, count(if(E.StartTime>NOW() - INTERVAL 1 MONTH && E.Archived = 0,1,NULL)) as MonthEventCount from Monitors as M left join Events as E on E.MonitorId = M.Id group by E.MonitorId order by Id";
	$result = mysql_query( $sql );
	if ( !$result )
		echo mysql_error();
	$monitors = array();
	$max_width = 0;
	$max_height = 0;
	while( $row = mysql_fetch_assoc( $result ) )
	{
		if ( $max_width < $row[Width] ) $max_width = $row[Width];
		if ( $max_height < $row[Height] ) $max_height = $row[Height];
		$sql = "select count(Id) as ZoneCount, count(if(Type='Active',1,NULL)) as ActZoneCount, count(if(Type='Inclusive',1,NULL)) as IncZoneCount, count(if(Type='Exclusive',1,NULL)) as ExcZoneCount, count(if(Type='Inactive',1,NULL)) as InactZoneCount from Zones where MonitorId = '$row[Id]'";
		$result2 = mysql_query( $sql );
		if ( !$result2 )
			echo mysql_error();
		$row2 = mysql_fetch_assoc( $result2 );
		$monitors[] = array_merge( $row, $row2 );
	}

	$sql = "select distinct Device from Monitors order by Device";
	$result = mysql_query( $sql );
	if ( !$result )
		echo mysql_error();
	$devices = array();

	while( $row = mysql_fetch_assoc( $result ) )
	{
		$ps_array = preg_split( "/\s+/", exec( "ps -edalf | grep 'zmc $row[Device]' | grep -v grep" ) );
		if ( $ps_array[3] )
		{
			$row['zmc'] = 1;
		}
		$ps_array = preg_split( "/\s+/", exec( "ps -edalf | grep 'zma $row[Device]' | grep -v grep" ) );
		if ( $ps_array[3] )
		{
			$row['zma'] = 1;
		}
		$devices[] = $row;
	}
?>
<html>
<head>
<title>ZM - Console</title>
<link rel="stylesheet" href="zmstyles.css" type="text/css">
<script language="JavaScript">
window.resizeTo(800,400)
function newWindow(Url,Name,Width,Height) {
        var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
</script>
</head>
<body>
<p class="head" align="center"><strong>Zone Monitor Console</strong></p>
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<tr>
<td class="smallhead" align="left"><?php echo count($monitors) ?> Monitors</td>
<td class="smallhead" align="center">Currently configured for <strong><?php echo $bandwidth ?></strong> bandwidth (change to
<?php if ( $bandwidth != "high" ) { ?> <a href="<?php echo $PHP_SELF ?>?new_bandwidth=high">high</a><?php } ?>
<?php if ( $bandwidth != "medium" ) { ?> <a href="<?php echo $PHP_SELF ?>?new_bandwidth=medium">medium</a><?php } ?>
<?php if ( $bandwidth != "low" ) { ?> <a href="<?php echo $PHP_SELF ?>?new_bandwidth=low">low</a><?php } ?> )</td>
<td class="smallhead" align="right"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=cycle', 'zmCycle', <?php echo $max_width+36 ?>, <?php echo $max_height+72 ?> );">Watch Monitors</a></td>
</tr>
</table>
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<form name="event_form" method="post" action="<?php echo $PHP_SELF ?>">
<input type="hidden" name="view" value="<?php echo $view ?>">
<input type="hidden" name="action" value="delete">
<tr><td align="left" class="smallhead">Id</td>
<td align="left" class="smallhead">Name</td>
<td align="left" class="smallhead">Device/Channel</td>
<td align="left" class="smallhead">Function</td>
<td align="left" class="smallhead">Dimensions</td>
<td align="right" class="smallhead">Events</td>
<td align="right" class="smallhead">Hour</td>
<td align="right" class="smallhead">Day</td>
<td align="right" class="smallhead">Week</td>
<td align="right" class="smallhead">Month</td>
<td align="right" class="smallhead">Archive</td>
<td align="right" class="smallhead">Zones</td>
<td align="center" class="smallhead">Delete</td>
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
		$device = $devices[$monitor[Device]];
		$event_count += $monitor[EventCount];
		$hour_event_count += $monitor[HourEventCount];
		$day_event_count += $monitor[DayEventCount];
		$week_event_count += $monitor[WeekEventCount];
		$month_event_count += $monitor[MonthEventCount];
		$arch_event_count += $monitor[ArchEventCount];
		$zone_count += $monitor[ZoneCount];
?>
<tr>
<td align="left" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=monitor&max_events=<?php echo MAX_EVENTS ?>&mid=<?php echo $monitor[Id] ?>', 'zm<?php echo $monitor[Name] ?>', <?php echo $monitor[Width]+72 ?>, <?php echo $monitor[Height]+360 ?> );"><?php echo $monitor[Id] ?>.</a></td>
<td align="left" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=monitor&max_events=<?php echo MAX_EVENTS ?>&mid=<?php echo $monitor[Id] ?>', 'zm<?php echo $monitor[Name] ?>', <?php echo $monitor[Width]+72 ?>, <?php echo $monitor[Height]+360 ?> );"><?php echo $monitor[Name] ?></a></td>
<td align="left" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=device&did=<?php echo $monitor[Device] ?>', 'zmDevice', 196, 164 );"><span class="<?php if ( $device[zmc] ) { if ( $device[zma] ) { echo "gretext"; } else { echo "oratext"; } } else { echo "redtext"; } ?>">/dev/video<?php echo $monitor[Device] ?> (<?php echo $monitor[Channel] ?>)</span></a></td>
<td align="left" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=function&mid=<?php echo $monitor[Id] ?>', 'zmFunction', 248, 72 );"><?php echo $monitor['Function'] ?></a></td>
<td align="left" class="text"><?php echo $monitor[Width] ?>x<?php echo $monitor[Height] ?>x<?php echo $monitor[Colours]*8 ?></td>
<td align="right" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=monitor&mid=<?php echo $monitor[Id] ?>', 'zm<?php echo $monitor[Name] ?>', <?php echo $monitor[Width]+72 ?>, <?php echo $monitor[Height]+360 ?> );"><?php echo $monitor[EventCount] ?></a></td>
<td align="right" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=monitor&period=hour&mid=<?php echo $monitor[Id] ?>', 'zm<?php echo $monitor[Name] ?>', <?php echo $monitor[Width]+72 ?>, <?php echo $monitor[Height]+360 ?> );"><?php echo $monitor[HourEventCount] ?></a></td>
<td align="right" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=monitor&period=day&mid=<?php echo $monitor[Id] ?>', 'zm<?php echo $monitor[Name] ?>', <?php echo $monitor[Width]+72 ?>, <?php echo $monitor[Height]+360 ?> );"><?php echo $monitor[DayEventCount] ?></a></td>
<td align="right" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=monitor&period=week&mid=<?php echo $monitor[Id] ?>', 'zm<?php echo $monitor[Name] ?>', <?php echo $monitor[Width]+72 ?>, <?php echo $monitor[Height]+360 ?> );"><?php echo $monitor[WeekEventCount] ?></a></td>
<td align="right" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=monitor&period=month&mid=<?php echo $monitor[Id] ?>', 'zm<?php echo $monitor[Name] ?>', <?php echo $monitor[Width]+72 ?>, <?php echo $monitor[Height]+360 ?> );"><?php echo $monitor[MonthEventCount] ?></a></td>
<td align="right" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=monitor&archived=1&mid=<?php echo $monitor[Id] ?>', 'zm<?php echo $monitor[Name] ?>', <?php echo $monitor[Width]+72 ?>, <?php echo $monitor[Height]+360 ?> );"><?php echo $monitor[ArchEventCount] ?></a></td>
<td align="right" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=zones&mid=<?php echo $monitor[Id] ?>', 'zmZones', <?php echo $monitor[Width]+72 ?>, <?php echo $monitor[Height]+232 ?> );"><?php echo $monitor[ZoneCount] ?></a></td>
<td align="center" class="text"><input type="checkbox" name="delete_mids[]" value="<?php echo $zone[Id] ?>"></td>
</tr>
<?php
	}
?>
<tr><td align="left" class="text">&nbsp;</td>
<td align="left" class="text">&nbsp;</td>
<td colspan="3" align="center"><input type="submit" value="Add New Monitor" class="form"></td>
<td align="right" class="text"><?php echo $event_count ?></td>
<td align="right" class="text"><?php echo $hour_event_count ?></td>
<td align="right" class="text"><?php echo $day_event_count ?></td>
<td align="right" class="text"><?php echo $week_event_count ?></td>
<td align="right" class="text"><?php echo $month_event_count ?></td>
<td align="right" class="text"><?php echo $arch_event_count ?></td>
<td align="right" class="text"><?php echo $zone_count ?></td>
<td align="center"><input type="submit" value="Delete" class="form"></td>
</tr>
</form>
</table>
</body>
</html>
<?php
}
elseif ( $view == "cycle" )
{
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

	header("Refresh: ".REFRESH_CYCLE."; URL='$PHP_SELF?view=cycle&mid=$next_mid'" );
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
	header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");                          // HTTP/1.0
?>
<html>
<head>
<title>ZM - Cycle Watch</title>
<link rel="stylesheet" href="zmstyles.css" type="text/css">
<script language="JavaScript">
function newWindow(Url,Name,Width,Height) {
        var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
</script>
</head>
<body>
<p class="head" align="center"><?php echo $monitor[Name] ?></p>
<a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=monitor&mid=<?php echo $monitor[Id] ?>', 'zm<?php echo $monitor[Name] ?>', <?php echo $monitor[Width]+72 ?>, <?php echo $monitor[Height]+360 ?> );"><img src='<?php echo $monitor[Name] ?>.jpg' border="0"></a>
</body>
</html>
<?php
}
elseif ( $view == "monitor" )
{
	$result = mysql_query( "select * from Monitors where Id = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$monitor = mysql_fetch_assoc( $result );
?>
<html>
<head>
<title>ZM - <?php echo $monitor[Name] ?> - Monitor</title>
<link rel="stylesheet" href="zmstyles.css" type="text/css">
<script language="JavaScript">
opener.location.reload();
window.focus();
</script>
</head>
<frameset rows="<?php echo $monitor[Height]+32 ?>,16,*" border="1" frameborder="no" framespacing="0">
<frame src="<?php echo $PHP_SELF ?>?view=watch&mid=<?php echo $monitor[Id] ?>" marginwidth="0" marginheight="0" name="MonitorStream" scrolling="no">
<frame src="<?php echo $PHP_SELF ?>?view=status&mid=<?php echo $monitor[Id] ?>" marginwidth="0" marginheight="0" name="MonitorStatus" scrolling="no">
<!--<frame src="<?php echo $PHP_SELF ?>?view=events&max_events=<?php echo MAX_EVENTS ?>&mid=<?php echo $monitor[Id] ?>" marginwidth="0" marginheight="0" name="MonitorEvents" scrolling="auto">-->
<frame src="<?php echo $PHP_SELF ?>?view=events&max_events=<?php echo $max_events ?>&period=<?php echo $period ?>&archived=<?php echo $archived ?>&mid=<?php echo $monitor[Id] ?>" marginwidth="0" marginheight="0" name="MonitorEvents" scrolling="auto">
</frameset>
<?php
}
elseif( $view == "watch" )
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
		header("Refresh: ".REFRESH_IMAGE."; URL='$PHP_SELF?view=watch&mid=$mid&mode=still'" );
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
		header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");                          // HTTP/1.0
	}
?>
<html>
<head>
<link rel="stylesheet" href="zmstyles.css" type="text/css">
<script language="JavaScript">
function closeWindow() {
	top.window.close();
}
</script>
</head>
<body>
<table width="96%" align="center" border="0" cellspacing="0" cellpadding="4">
<tr>
<td width="33%" align="left" class="text"><b><?php echo $monitor[Name] ?></b></td>
<?php if ( $mode == "stream" ) { ?>
<td width="34%" align="center" class="text"><a href="<?php echo $PHP_SELF ?>?view=watch&mode=still&mid=<?php echo $mid ?>">Stills</a></td>
<?php } elseif ( canStream() ) { ?>
<td width="34%" align="center" class="text"><a href="<?php echo $PHP_SELF ?>?view=watch&mode=stream&mid=<?php echo $mid ?>">Stream</a></td>
<?php } else { ?>
<td width="34%" align="center" class="text">&nbsp;</td>
<?php } ?>
<td width="33%" align="right" class="text"><a href="javascript: closeWindow();">Close</a></td>
</tr>
<?php if ( $mode == "stream" )
{
	$stream_src = ZMS_PATH."?monitor=$monitor[Id]&idle=".STREAM_IDLE_DELAY."&refresh=".STREAM_FRAME_DELAY;
	if ( isNetscape() )
	{
?>
<tr><td colspan="3" align="center"><img src="<?php echo $stream_src ?>" border="0" width="<?php echo $monitor[Width] ?>" height="<?php echo $monitor[Height] ?>"></td></tr>
<?php
	}
	else
	{
?>
<tr><td colspan="3" align="center"><applet code="com.charliemouse.cambozola.Viewer" archive="<?php echo CAMBOZOLA_PATH ?>" align="middle" width="<?php echo $monitor[Width] ?>" height="<?php echo $monitor[Height] ?>"><param name="url" value="<?php echo $stream_src ?>"></applet></td></tr>
<?php
	}
}
else
{
?>
<tr><td colspan="3" align="center"><img src="<?php echo $monitor[Name] ?>.jpg" border="0" width="<?php echo $monitor[Width] ?>" height="<?php echo $monitor[Height] ?>"></td></tr>
<?php
}
?>
</table>
</body>
</html>
<?php
}
elseif ( $view == "status" )
{
	$status = exec( escapeshellcmd( ZMU_PATH." -m $mid -s" ) );
	$status_string = "Unknown";
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
		$class = "oratext";
	}
	header("Refresh: ".REFRESH_STATUS."; URL='$PHP_SELF?view=status&mid=$mid&last_status=$status'" );
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
	header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");                          // HTTP/1.0
?>
<html>
<head>
<link rel="stylesheet" href="zmstyles.css" type="text/css">
<script language="JavaScript">
<?php
	if ( $status > 0 && $last_status == 0 )
	{
?>
top.window.focus();
<?php
//document.write( "\a" );
	}
?>
</script>
</head>
<body>
<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0"><tr><td class="<?php echo $class ?>" align="center" valign="middle">Status: <?php echo $status_string ?></td></tr></table>
</body>
</html>
<?php
}
elseif ( $view == "events" )
{
	if ( !$archived )
	{
		if ( $max_events )
		{
			header("Refresh: ".REFRESH_EVENTS."; URL='$PHP_SELF?view=events&mid=$mid&max_events=$max_events'" );
		}
		else
		{
			header("Refresh: ".REFRESH_EVENTS_ALL."; URL='$PHP_SELF?view=events&period=$period&archived=$archived&mid=$mid'" );
		}
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
		header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");                          // HTTP/1.0
	}
?>
<html>
<head>
<title>ZM - <?php echo $monitor ?> - Events <?php if ( $archived ) { ?>Archive<?php } ?></title>
<link rel="stylesheet" href="zmstyles.css" type="text/css">
<script language="JavaScript">
function newWindow(Url,Name) {
        var Name = window.open(Url,Name,"resizable,scrollbars,width=420,height=500");
}
function closeWindow() {
        top.window.close();
}
function checkAll(form,name){
	for (var i = 0; i < form.elements.length; i++)
		if (form.elements[i].name.indexOf(name) == 0)
			form.elements[i].checked = 1;
}
</script>
</head>
<body>
<form name="event_form" method="post" action="<?php echo $PHP_SELF ?>">
<input type="hidden" name="view" value="<?php echo $view ?>">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="mid" value="<?php echo $mid ?>">
<?php if ( $max_events ) { ?>
<input type="hidden" name="max_events" value="<?php echo $max_events ?>">
<?php } ?>
<?php if ( $period ) { ?>
<input type="hidden" name="period" value="<?php echo $period ?>">
<?php } ?>
<?php if ( $archived ) { ?>
<input type="hidden" name="archived" value="<?php echo $archived ?>">
<?php } ?>
<center><table width="96%" align="center" border="0" cellspacing="1" cellpadding="0">
<tr>
<td valign="top"><table border="0" cellspacing="0" cellpadding="0" width="100%">
<?php
	$sql = "select E.Id, E.Name,unix_timestamp(E.StartTime) as Time,E.Length,E.Frames,E.AlarmFrames,E.AvgScore,E.MaxScore from Monitors as M, Events as E where M.Id = '$mid' and M.Id = E.MonitorId and E.Archived = ".($archived?"1":"0");
	if ( $period )
		$sql .= " and E.StartTime >= now() - interval 1 $period";
	$sql .= " order by E.Id desc";
	if ( $max_events )
		$sql .= " limit 0,$max_events";
	$result = mysql_query( $sql );
	if ( !$result )
	{
		die( mysql_error() );
	}
	$n_rows = mysql_num_rows( $result );
?>
<tr>
<td class="text"><b><?php if ( $max_events ) {?>Last <?php } ?><?php echo $n_rows ?> events</b></td>
<?php if ( !$max_events ) { ?>
<td align="center" class="text"><a href="<?php echo $PHP_SELF ?>?view=events&mid=<?php echo $mid ?>&max_events=<?php echo MAX_EVENTS ?>">Recent</a></td>
<?php } ?>
<?php if ( $archived || $max_events ) { ?>
<td align="center" class="text"><a href="<?php echo $PHP_SELF ?>?view=events&mid=<?php echo $mid ?>">All</a></td>
<?php } ?>
<?php if ( !$archived ) { ?>
<td align="center" class="text"><a href="<?php echo $PHP_SELF ?>?view=events&mid=<?php echo $mid ?>&archived=1">Archive</a></td>
<?php } ?>
<td align="right" class="text"><a href="javascript: checkAll( event_form, 'delete_eids' );">Check All</a></td>
</tr>
<tr><td colspan="5" class="text">&nbsp;</td></tr>
<tr><td colspan="5"><table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr align="center">
<td width="4%" class="text">Id</td>
<td width="24%" class="text">Name</td>
<td class="text">Time</td>
<td class="text">Secs</td>
<td class="text">Frames</td>
<td class="text">Score</td>
<td class="text">Delete</td>
</tr>
<?php
	while( $row = mysql_fetch_assoc( $result ) )
	{
?>
<tr>
<td align="center" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=event&eid=<?php echo $row[Id] ?>', 'zmEvent' );"><?php echo $row[Id] ?></a></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=event&eid=<?php echo $row[Id] ?>', 'zmEvent' );"><?php echo $row[Name] ?></a></td>
<td align="center" class="text"><?php echo strftime( "%m/%d %H:%M:%S", $row[Time] ) ?></td>
<td align="center" class="text"><?php echo $row[Length] ?></td>
<td align="center" class="text"><?php echo $row[Frames] ?> (<?php echo $row[AlarmFrames] ?>)</td>
<td align="center" class="text"><?php echo $row[AvgScore] ?> (<?php echo $row[MaxScore] ?>)</td>
<td align="center" class="text"><input type="checkbox" name="delete_eids[]" value="<?php echo $row[Id] ?>"></td>
</tr>
<?php
	}
?>
</table></td></tr>
</table></td>
</tr>
<tr><td align="right"><input type="submit" value="Delete" class="form"></td></tr>
</table></center>
</form>
</body>
</html>
<?php
}
elseif( $view == "image" )
{
	$result = mysql_query( "select * from Frames where EventID = '$eid' and FrameId = '$fid'" );
	if ( !$result )
		die( mysql_error() );
	$frame = mysql_fetch_assoc( $result );

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

?>
<html>
<head>
<title>ZM - Image <?php echo $eid."-".$fid ?></title>
<link rel="stylesheet" href="zmstyles.css" type="text/css">
<script language="JavaScript">
window.focus();
function newWindow(Url,Name,Width,Height) {
        var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function closeWindow() {
        window.close();
}
function deleteImage() {
	opener.location.href = "<?php echo $PHP_SELF ?>?view=delete&eid=<?php echo $eid ?>";
        window.close();
}
</script>
</head>
<body>
<table border="0">
<tr><td colspan="2" class="text"><b>Image <?php echo $eid."-".$fid ?></b></td>
<td align="center" class="text"><a href="javascript: deleteImage();">Delete</a></td>
<td align="right" class="text"><a href="javascript: closeWindow();">Close</a></td>
</tr>
<tr><td colspan="4"><img src="<?php echo $image_path ?>" width="352" height="288" border="0"></td></tr>
<tr>
<?php if ( $fid > 1 ) { ?>
<td width="25%" class="text"><a href="<?php echo $PHP_SELF ?>?view=image&eid=<?php echo $eid ?>&fid=<?php echo $first_fid ?>">First</a></td>
<?php } else { ?>
<td width="25%" class="text">&nbsp;</td>
<?php } if ( $fid > 1 ) { ?>
<td width="25%" class="text"><a href="<?php echo $PHP_SELF ?>?view=image&eid=<?php echo $eid ?>&fid=<?php echo $prev_fid ?>">Prev</a></td>
<?php } else { ?>
<td width="25%" class="text">&nbsp;</td>
<?php } if ( $fid < $max_fid ) { ?>
<td width="25%" class="text"><a href="<?php echo $PHP_SELF ?>?view=image&eid=<?php echo $eid ?>&fid=<?php echo $next_fid ?>">Next</a></td>
<?php } else { ?>
<td width="25%" class="text">&nbsp;</td>
<?php } if ( $fid < $max_fid ) { ?>
<td width="25%" class="text"><a href="<?php echo $PHP_SELF ?>?view=image&eid=<?php echo $eid ?>&fid=<?php echo $last_fid ?>">Last</a></td>
<?php } else { ?>
<td width="25%" class="text">&nbsp;</td>
<?php } ?>
</tr>
</table>
</body>
</html>
<?php
}
elseif( $view == "event" )
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
?>
<html>
<head>
<title>ZM - Event - <?php echo $event[Name] ?></title>
<link rel="stylesheet" href="zmstyles.css" type="text/css">
<script language="JavaScript">
opener.location.reload();
window.focus();
function refreshWindow() {
        window.location.reload();
}
function closeWindow() {
        window.close();
}
function newWindow(Url,Name,Width,Height) {
        var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td colspan="6" align="left" class="text">
<form method="post" action="<?php echo $PHP_SELF ?>">
<input type="hidden" name="view" value="<?php echo $view ?>">
<input type="hidden" name="action" value="rename">
<input type="hidden" name="eid" value="$eid">
<input type="text" size="16" name="event_name" value="<?php echo $event[Name] ?>" class="form">
<input type="submit" value="Rename" class="form"></td>
</tr>
<tr>
<td align="center" class="text"><a href="javascript: refreshWindow();">Refresh</a></td>
<td align="center" class="text"><a href="<?php echo $PHP_SELF ?>?view=delete&eid=<?php echo $eid ?>">Delete</a></td>
<td align="center" class="text"><a href="<?php echo $PHP_SELF ?>?view=<?php echo $view ?>&action=archive&mid=<?php echo $event[MonitorName] ?>&eid=<?php echo $eid ?>">Archive</a></td>
<?php if ( $mode == "stream" ) { ?>
<td align="center" class="text"><a href="<?php echo $PHP_SELF ?>?view=event&mode=still&mid=<?php echo $mid ?>&eid=<?php echo $eid ?>">Stills</a></td>
<?php } elseif ( canStream() ) { ?>
<td align="center" class="text"><a href="<?php echo $PHP_SELF ?>?view=event&mode=stream&mid=<?php echo $mid ?>&eid=<?php echo $eid ?>">Stream</a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
<td align="center" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=video&eid=<?php echo $eid ?>', 'zmVideo', 100, 80 );">Video</a></td>
<td align="right" class="text"><a href="javascript: closeWindow();">Close</a></td>
</tr>



<?php if ( $mode == "stream" )
{
	$stream_src = ZMS_PATH."?path=".ZMS_EVENT_PATH."&event=$eid&refresh=".STREAM_EVENT_DELAY;
	if ( isNetscape() )
	{
?>
<tr><td colspan="6" align="center"><img src="<?php echo $stream_src ?>" border="0" width="<?php echo $event[Width] ?>" height="<?php echo $event[Height] ?>"></td></tr>
<?php
	}
	else
	{
?>
<tr><td colspan="6" align="center"><applet code="com.charliemouse.cambozola.Viewer" archive="<?php echo CAMBOZOLA_PATH ?>" align="middle" width="<?php echo $event[Width] ?>" height="<?php echo $event[Height] ?>"><param name="url" value="<?php echo $stream_src ?>"></applet></td></tr>
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
		if ( $scale == 1 )
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
					$command = "jpegtopnm -dct fast $anal_image | pnmscalefixed $fraction | ppmtojpeg --dct=fast > $thumb_image";
				else
					$command = "jpegtopnm -dct fast $capt_image | pnmscalefixed $fraction | ppmtojpeg --dct=fast > $thumb_image";
				#exec( escapeshellcmd( $command ) );
				exec( $command );
			}
		}
?>
<td align="center" width="88"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=image&eid=<?php echo $eid ?>&fid=<?php echo $frame_id ?>', 'zmImage', <?php echo $event[Width]+48 ?>, <?php echo $event[Height]+72 ?> );"><img src="<?php echo $thumb_image ?>" width="<?php echo $thumb_width ?>" height="<? echo $thumb_height ?>" border="0" alt="<?php echo $frame_id ?>/<?php echo $row[Score] ?>"></a></td>
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
</table>
</body>
</html>
<?php
}
elseif( $view == "zones" )
{
	$status = exec( escapeshellcmd( ZMU_PATH." -m $mid -z" ) );

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
<title>ZM - <?php echo $monitor[Name] ?> - Zones</title>
<link rel="stylesheet" href="zmstyles.css" type="text/css">
<script language="JavaScript">
window.focus();
function newWindow(Url,Name,Width,Height) {
        var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function closeWindow() {
        window.close();
}
</script>
</head>
<body>
<map name="zonemap">
<?php
	foreach( $zones as $zone )
	{
?>
<area shape="rect" coords="<?php echo "$zone[LoX],$zone[LoY],$zone[HiX],$zone[HiY]" ?>" href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=zone&mid=<?php echo $mid ?>&zid=<?php echo $zone[Id] ?>', 'zmZone', 360, 480 );">
<?php
	}
?>
<area shape="default" nohref>
</map>
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<tr>
<td width="33%" align="left" class="text">&nbsp;</td>
<td width="34%" align="center" class="head"><strong><?php echo $monitor[Name] ?> Zones</strong></td>
<td width="33%" align="right" class="text"><a href="javascript: closeWindow();">Close</a></td>
</tr>
<tr><td colspan="3" align="center"><img src="<?php echo $image ?>" usemap="#zonemap" width="352" height="288" border="0"></td></tr>
</table>
<table align="center" border="0" cellspacing="0" cellpadding="0" width="96%">
<form name="event_form" method="post" action="<?php echo $PHP_SELF ?>">
<input type="hidden" name="view" value="<?php echo $zones ?>">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="mid" value="<?php echo $mid ?>">
<tr><td align="center" class="smallhead">Id</td>
<td align="center" class="smallhead">Name</td>
<td align="center" class="smallhead">Type</td>
<td align="center" class="smallhead">Units</td>
<td align="center" class="smallhead">Dimensions</td>
<td align="center" class="smallhead">Delete</td>
</tr>
<?php
	foreach( $zones as $zone )
	{
?>
<tr>
<td align="center" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=zone&mid=<?php echo $mid ?>&zid=<?php echo $zone[Id] ?>', 'zmZone', 360, 480 );"><?php echo $zone[Id] ?>.</a></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=zone&mid=<?php echo $mid ?>&zid=<?php echo $zone[Id] ?>', 'zmZone', 360, 480 );"><?php echo $zone[Name] ?></a></td>
<td align="center" class="text"><?php echo $zone['Type'] ?></td>
<td align="center" class="text"><?php echo $zone[Units] ?></td>
<td align="center" class="text"><?php echo $zone[LoX] ?>,<?php echo $zone[LoY] ?>-<?php echo $zone[HiX] ?>,<?php echo $zone[HiY]?></td>
<td align="center" class="text"><input type="checkbox" name="delete_zids[]" value="<?php echo $zone[Id] ?>"></td>
</tr>
<?php
	}
?>
<tr>
<td align="center" class="text">&nbsp;</td>
<td colspan="4" align="center"><input type="submit" value="Add New Zone" class="form"></td>
<td align="center"><input type="submit" value="Delete" class="form"></td>
</tr>
</form>
</table>
</body>
</html>
<?php
}
elseif( $view == "zone" )
{
	$result = mysql_query( "select * from Monitors where Id = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$monitor = mysql_fetch_assoc( $result );

	$result = mysql_query( "select * from Zones where MonitorId = '$mid' and Id = '$zid'" );
	if ( !$result )
		die( mysql_error() );
	$zone = mysql_fetch_assoc( $result );
?>
<html>
<head>
<title>ZM - <?php echo $monitor[Name] ?> - Zone <?php echo $zone[Id] ?></title>
<link rel="stylesheet" href="zmstyles.css" type="text/css">
<script language="JavaScript">
<?php
if ( $refresh_parent )
{
?>
opener.location.reload();
<?php
}
?>
window.focus();
function closeWindow() {
        window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td colspan="2" align="left" class="head">Monitor <?php echo $monitor[Name] ?> - Zone <?php echo $zone[Id] ?></td>
</tr>
<form method="post" action="<?php echo $PHP_SELF ?>">
<input type="hidden" name="view" value="<?php echo $view ?>">
<input type="hidden" name="action" value="zone">
<input type="hidden" name="mid" value="<?php echo $mid ?>">
<input type="hidden" name="zid" value="<?php echo $zid ?>">
<tr>
<td align="left" class="smallhead">Parameter</td><td align="left" class="smallhead">Value</td>
</tr>
<tr><td align="left" class="text">Name</td><td align="left" class="text"><input type="text" name="new_name" value="<?php echo $zone[Name] ?>" size="12" class="form"></td></tr>
<tr><td align="left" class="text">Type</td><td align="left" class="text"><select name="new_type" class="form">
<?php
	foreach ( getEnumValues( 'Zones', 'Type' ) as $opt_type )
	{
?>
<option value="<?php echo $opt_type ?>"<?php if ( $opt_type == $zone['Type'] ) { ?> selected<?php } ?>><?php echo $opt_type ?></option>
<?php
	}
?>
</select></td></tr>
<tr><td align="left" class="text">Units</td><td align="left" class="text"><select name="new_units" class="form">
<?php
	foreach ( getEnumValues( 'Zones', 'Units' ) as $opt_units )
	{
?>
<option value="<?php echo $opt_units ?>"<?php if ( $opt_units == $zone['Units'] ) { ?> selected<?php } ?>><?php echo $opt_units ?></option>
<?php
	}
?>
</select></td></tr>
<tr><td align="left" class="text">Low X (left)</td><td align="left" class="text"><input type="text" name="new_lo_x" value="<?php echo $zone[LoX] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Low Y (top)</td><td align="left" class="text"><input type="text" name="new_lo_y" value="<?php echo $zone[LoY] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">High X (right)</td><td align="left" class="text"><input type="text" name="new_hi_x" value="<?php echo $zone[HiX] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">High Y (bottom)</td><td align="left" class="text"><input type="text" name="new_hi_y" value="<?php echo $zone[HiY] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Alarm Colour (RGB)</td><td align="left" class="text"><input type="text" name="new_alarm_rgb" value="<?php echo $zone[AlarmRGB] ?>" size="12" class="form"></td></tr>
<tr><td align="left" class="text">Alarm Threshold (0>=?<=255)</td><td align="left" class="text"><input type="text" name="new_alarm_threshold" value="<?php echo $zone[AlarmThreshold] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Minimum Alarmed Area</td><td align="left" class="text"><input type="text" name="new_min_alarm_pixels" value="<?php echo $zone[MinAlarmPixels] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text">Maximum Alarmed Area</td><td align="left" class="text"><input type="text" name="new_max_alarm_pixels" value="<?php echo $zone[MaxAlarmPixels] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text">Filter Width (pixels)</td><td align="left" class="text"><input type="text" name="new_filter_x" value="<?php echo $zone[FilterX] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Filter Height (pixels)</td><td align="left" class="text"><input type="text" name="new_filter_y" value="<?php echo $zone[FilterY] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Minimum Filtered Area</td><td align="left" class="text"><input type="text" name="new_min_filter_pixels" value="<?php echo $zone[MinFilterPixels] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text">Maximum Filtered Area</td><td align="left" class="text"><input type="text" name="new_max_filter_pixels" value="<?php echo $zone[MaxFilterPixels] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text">Minimum Blob Area</td><td align="left" class="text"><input type="text" name="new_min_blob_pixels" value="<?php echo $zone[MinBlobPixels] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text">Maximum Blob Area</td><td align="left" class="text"><input type="text" name="new_max_blob_pixels" value="<?php echo $zone[MaxBlobPixels] ?>" size="6" class="form"></td></tr>
<tr><td align="left" class="text">Minimum Blobs</td><td align="left" class="text"><input type="text" name="new_min_blobs" value="<?php echo $zone[MinBlobs] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Maximum Blobs</td><td align="left" class="text"><input type="text" name="new_max_blobs" value="<?php echo $zone[MaxBlobs] ?>" size="4" class="form"></td></tr>
<tr><td colspan="2" align="left" class="text">&nbsp;</td></tr>
<tr>
<td align="left"><input type="submit" value="Update" class="form"></td>
<td align="left"><input type="button" value="Cancel" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
<?php
}
elseif( $view == "video" )
{
	$result = mysql_query( "select E.*,M.Name as MonitorName, M.Colours from Events as E, Monitors as M where E.Id = '$eid' and E.MonitorId = M.Id" );
	if ( !$result )
		die( mysql_error() );
	$event = mysql_fetch_assoc( $result );

	$event_dir = "events/$event[MonitorName]/".sprintf( "%04d", $eid );
	$param_file = $event_dir."/mpeg.param";
	$video_name = preg_replace( "/\\s/", "_", $event[Name] ).".mpeg";
	$video_file = $event_dir."/".$video_name;

	if ( !file_exists( $video_file ) )
	{
		$fp = fopen( $param_file, "w" );

		fputs( $fp, "PATTERN		IBBPBBPBBPBBPBB\n" );
		fputs( $fp, "OUTPUT		$video_file\n" );

		fputs( $fp, "BASE_FILE_FORMAT	JPEG\n" );
		fputs( $fp, "GOP_SIZE	30\n" );
		fputs( $fp, "SLICES_PER_FRAME	1\n" );

		fputs( $fp, "PIXEL		HALF\n" );
		fputs( $fp, "RANGE		10\n" );
		fputs( $fp, "PSEARCH_ALG	LOGARITHMIC\n" );
		fputs( $fp, "BSEARCH_ALG	CROSS2\n" );
		fputs( $fp, "IQSCALE		8\n" );
		fputs( $fp, "PQSCALE		10\n" );
		fputs( $fp, "BQSCALE		25\n" );

		fputs( $fp, "REFERENCE_FRAME	ORIGINAL\n" );
		fputs( $fp, "FRAME_RATE 24\n" );

		if ( $event[Colours] == 1 )
			fputs( $fp, "INPUT_CONVERT	jpegtopnm * | pgmtoppm white | ppmtojpeg\n" );
		else
			fputs( $fp, "INPUT_CONVERT	*\n" );

		fputs( $fp, "INPUT_DIR	$event_dir\n" );

		fputs( $fp, "INPUT\n" );
		for ( $i = 1; $i < $event[Frames]; $i++ )
		{
			fputs( $fp, "capture-".sprintf( "%03d", $i ).".jpg\n" );
			fputs( $fp, "capture-".sprintf( "%03d", $i ).".jpg\n" );
		}
		fputs( $fp, "END_INPUT\n" );
		fclose( $fp );

		exec( escapeshellcmd( "./mpeg_encode $param_file >$event_dir/mpeg.log" ) );
	}

	//chdir( $event_dir );
	//header("Content-type: video/mpeg");
	//header("Content-Disposition: inline; filename=$video_name");
	header("Location: $video_file" );
}
elseif ( $view == "device" )
{
	$ps_array = preg_split( "/\s+/", exec( "ps -edalf | grep 'zmc $did' | grep -v grep" ) );
	if ( $ps_array[3] )
	{
		$zmc = 1;
	}
	$ps_array = preg_split( "/\s+/", exec( "ps -edalf | grep 'zma $did' | grep -v grep" ) );
	if ( $ps_array[3] )
	{
		$zma = 1;
	}
?>
<html>
<head>
<title>ZM - Device - /dev/video<?php echo $did ?></title>
<link rel="stylesheet" href="zmstyles.css" type="text/css">
<script language="JavaScript">
<?php
if ( $zmc_status != $zmc_action || $zma_status != $zma_action )
{
?>
opener.location.reload();
<?php
}
?>
window.focus();
function refreshWindow() {
        window.location.reload();
}
function closeWindow() {
        window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="2" width="100%">
<tr>
<td colspan="2" align="left" class="head">Device Daemon Status</td>
</tr>
<form method="post" action="<?php echo $PHP_SELF ?>">
<input type="hidden" name="view" value="<?php echo $view ?>">
<input type="hidden" name="action" value="device">
<input type="hidden" name="zmc_status" value="<?php echo $zmc ?>">
<input type="hidden" name="zma_status" value="<?php echo $zma ?>">
<input type="hidden" name="did" value="<?php echo $did ?>">
<tr>
<td align="left" class="smallhead">Daemon</td><td align="left" class="smallhead">Active</td>
</tr>
<tr>
<td align="left" class="text">Capture</td><td align="left" class="text"><input type="checkbox" name="zmc_action" value="1"<?php if ( $zmc ) { echo " checked"; } ?> class="form"></td>
</tr>
<tr>
<td align="left" class="text">Analysis</td><td align="left" class="text"><input type="checkbox" name="zma_action" value="1"<?php if ( $zma ) { echo " checked"; } ?> class="form"></td>
</tr>
<tr>
<td colspan="2" align="left" class="text">&nbsp;</td>
</tr>
<tr>
<td align="left"><input type="submit" value="Update" class="form"></td>
<td align="left"><input type="button" value="Cancel" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
<?php
}
elseif ( $view == "function" )
{
	$result = mysql_query( "select * from Monitors where Id = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$monitor = mysql_fetch_assoc( $result );
?>
<html>
<head>
<title>ZM - Function - <?php echo $monitor[Name] ?></title>
<link rel="stylesheet" href="zmstyles.css" type="text/css">
<script language="JavaScript">
<?php
if ( $refresh_parent )
{
?>
opener.location.reload();
<?php
}
?>
window.focus();
function refreshWindow() {
        window.location.reload();
}
function closeWindow() {
        window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td colspan="2" align="center" class="head">Monitor '<?php echo $monitor[Name] ?>' Function</td>
</tr>
<tr>
<form method="post" action="<?php echo $PHP_SELF ?>">
<input type="hidden" name="view" value="<?php echo $view ?>">
<input type="hidden" name="action" value="function">
<input type="hidden" name="mid" value="<?php echo $mid ?>">
<td colspan="2" align="center"><select name="new_function" class="form">
<?php
	foreach ( getEnumValues( 'Monitors', 'Function' ) as $opt_function )
	{
?>
<option value="<?php echo $opt_function ?>"<?php if ( $opt_function == $monitor['Function'] ) { ?> selected<?php } ?>><?php echo $opt_function ?></option>
<?php
	}
?>
</select></td>
</tr>
<tr>
<td align="center"><input type="submit" value="Update" class="form"></td>
<td align="center"><input type="button" value="Cancel" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
<?php
}
elseif ( $view == "none" )
{
?>
<html>
<head>
<script language="JavaScript">
window.close();
</script>
</head>
</html>
<?php
}

function isNetscape()
{
	global $HTTP_USER_AGENT;

	return( preg_match( '/Mozilla\/([0-9].[0-9]{1,2})/', $HTTP_USER_AGENT ) );
}

function canStream()
{
	return( isNetscape() || file_exists( CAMBOZOLA_PATH ) );
}

function startDaemon( $daemon, $did )
{
	$ps_command = "ps -edalf | grep '$daemon $did' | grep -v grep";
	$ps_array = preg_split( "/\s+/", exec( $ps_command ) );
	$pid = $ps_array[3];
	if ( $pid )
	{
		exec( "kill -HUP $pid" );
		return;
	}
	$command = ZM_PATH."/$daemon $did".' 2>/dev/null >&- <&- >/dev/null &';
	exec( $command );
	$ps_array = preg_split( "/\s+/", exec( $ps_command ) );
	while ( !$pid )
	{
		sleep( 1 );
		$ps_array = preg_split( "/\s+/", exec( $ps_command ) );
		$pid = $ps_array[3];
	}
}

function stopDaemon( $daemon, $did )
{
	$ps_command = "ps -edalf | grep '$daemon $did' | grep -v grep";
	$ps_array = preg_split( "/\s+/", exec( $ps_command ) );
	if ( $ps_array[3] )
	{
		$pid = $ps_array[3];
		exec( "kill -TERM $pid" );
	}
	else
	{
		return;
	}
	while( $pid )
	{
		sleep( 1 );
		$ps_array = preg_split( "/\s+/", exec( $ps_command ) );
		$pid = $ps_array[3];
	}
}

function getEnumValues( $table, $column )
{
	$enum_values = array();
	$result = mysql_query( "DESCRIBE $table $column" );
	if ( $result )
	{
		$row = mysql_fetch_assoc($result);
		preg_match_all( "/'([^']+)'/", $row[Type], $enum_matches );
		$enum_values = $enum_matches[1];
	}
	else
	{
		echo mysql_error();
	}
	return $enum_values;
}
?>
