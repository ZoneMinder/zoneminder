<?php

//
// ZoneMinder WML interface file, $Date$, $Revision$
// Copyright (C) 2002  Philip Coombes
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

//
// Note: This is _really_ prototypical and not intended to be much
// use at present. However I'm working on a much nicer version with
// built in brower capability detection which should be much nicer.
//

ini_set( "session.name", "ZMSESSID" );
ini_set( "session.auto_start", "1" );
ini_set( "session.use_cookies", "0" );
ini_set( "session.use_trans_sid", "1" );
ini_set( "arg_separator.output", "&amp;" );
ini_set( "url_rewriter.tags", ini_get( "url_rewriter.tags" ).",card=ontimer" );

session_start();

$bandwidth = "mobile";

require_once( 'zmconfig.php' );
require_once( 'zmdb.php' );
require_once( 'zmfuncs.php' );
require_once( 'zmactions.php' );

define( "WAP_COOKIES", false );

header("Content-type: text/vnd.wap.wml"); 
header("Cache-Control: no-cache, must-revalidate"); 
header("Pragma: no-cache"); 
echo( '<?xml version="1.0"?>'."\n" );
echo( '<!DOCTYPE wml PUBLIC "-//WAPFORUM//DTD WML 1.1//EN" "http://www.wapforum.org/DTD/wml_1.1.xml">'."\n" );

if ( !$view )
{
	$view = "console";
}

if ( $view == "console" )
{
	if ( !$HTTP_SESSION_VARS[event_reset_time] )
		$HTTP_SESSION_VARS[event_reset_time] = "2000-01-01 00:00:00";

	$db_now = strftime( "%Y-%m-%d %H:%M:%S" );
	$sql = "select M.*, count(E.Id) as EventCount, count(if(E.StartTime>'$HTTP_SESSION_VARS[event_reset_time]' && E.Archived = 0,1,NULL)) as ResetEventCount, count(if(E.StartTime>'$db_now' - INTERVAL 1 HOUR && E.Archived = 0,1,NULL)) as HourEventCount, count(if(E.StartTime>'$db_now' - INTERVAL 1 DAY && E.Archived = 0,1,NULL)) as DayEventCount from Monitors as M left join Events as E on E.MonitorId = M.Id group by E.MonitorId order by Id";
	$result = mysql_query( $sql );
	if ( !$result )
		echo mysql_error();
	$monitors = array();
	$max_width = 0;
	$max_height = 0;
	$cycle_count = 0;
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
		if ( $row['Function'] != 'None' ) $cycle_count++;
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
<wml>
<card id="zmConsole" title="ZM - Console" ontimer="<?php echo $PHP_SELF ?>?view=<?php echo $view ?>">
<timer value="<?php echo REFRESH_MAIN*10 ?>"/>
<p mode="nowrap" align="center"><strong>ZM - Console</strong></p>
<p mode="nowrap" align="center"><?php echo count($monitors) ?> Monitors - <?php echo strftime( "%T" ) ?></p>
<p mode="nowrap" align="center"><?php echo $HTTP_SESSION_VARS[event_reset_time] ?></p>
<p align="center">
<table columns="3">
<tr>
<td>Name</td>
<td>Func</td>
<td>Events</td>
</tr>
<?php
	$reset_event_count = 0;
	foreach( $monitors as $monitor )
	{
		$device = $devices[$monitor[Device]];
		$reset_event_count += $monitor[ResetEventCount];
?>
<tr>
<td><a href="<?php echo $PHP_SELF ?>?view=feed&amp;mid=<?php echo $monitor[Id] ?>"><?php echo $monitor[Name] ?></a></td>
<td><a href="<?php echo $PHP_SELF ?>?view=function&amp;mid=<?php echo $monitor[Id] ?>"><?php echo substr( $monitor['Function'], 0, 1 ) ?></a></td>
<td><a href="<?php echo $PHP_SELF ?>?view=events&amp;mid=<?php echo $monitor[Id] ?>"><?php echo $monitor[ResetEventCount] ?></a></td>
</tr>
<?php
	}
?>
</table>
</p>
<p mode="nowrap" align="center"><a href="<?php echo $PHP_SELF ?>?view=<?php echo $view ?>&amp;action=reset">Reset Event Counts</a></p>
</card>
</wml>
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
<a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=watch&mid=<?php echo $monitor[Id] ?>', 'zmWatch<?php echo $monitor[Name] ?>', <?php echo $monitor[Width]+72 ?>, <?php echo $monitor[Height]+360 ?> );"><img src='<?php echo $monitor[Name] ?>.jpg' border="0"></a>
</body>
</html>
<?php
}
elseif( $view == "feed" )
{
	$result = mysql_query( "select * from Monitors where Id = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$monitor = mysql_fetch_assoc( $result );
	$browser = array();
	$browser[Width] = 100;
	$browser[Height] = 80;

	// Generate an image
	$status = exec( escapeshellcmd( ZMU_PATH." -m $mid -i" ) );
	$monitor_image = "$monitor[Name].jpg";
	$image_time = filemtime( $monitor_image );
	$browser_image = "$monitor[Name]-wap-$image_time.jpg";
	$command = NETPBM_DIR."/jpegtopnm -dct fast $monitor_image | ".NETPBM_DIR."/pnmscale -xysize $browser[Width] $browser[Height] | ".NETPBM_DIR."/ppmtojpeg > $browser_image";
	exec( $command );
?>
<wml>
<card id="zmFeed" title="ZM - <?php echo $monitor[Name] ?>" ontimer="<?php echo $PHP_SELF ?>?view=<?php echo $view ?>&amp;mid=<?php echo $mid ?>">
<timer value="<?php echo REFRESH_IMAGE*10 ?>"/>
<p mode="nowrap" align="center"><strong>ZM - <?php echo $monitor[Name] ?></strong></p>
<p mode="nowrap" align="center"><img src="<?php echo $browser_image ?>" alt="<?php echo $monitor[Name] ?>" hspace="0" vspace="0" align="middle"/></p>
</card>
</wml>
<?php
	flush();
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
	$new_alarm = ( $status > 0 && $last_status == 0 );

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
	if ( ALARM_POPUP && $new_alarm )
	{
?>
top.window.focus();
<?php
	}
?>
</script>
</head>
<body>
<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0"><tr><td class="<?php echo $class ?>" align="center" valign="middle">Status: <?php echo $status_string ?></td></tr></table>
<?php
	if ( ALARM_SOUND && $status == 1 )
	{
?>
<embed src="<?php echo ALARM_SOUND ?>" autostart="yes" hidden="true"></embed>
<?php
	}
?>
</body>
</html>
<?php
}
elseif ( $view == "events" )
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
	if ( !$sort_asc ) $sort_asc = 0;
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
<form name="event_form" method="get" action="<?php echo $PHP_SELF ?>">
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
	$sql .= " order by $sort_column $sort_order";
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
<td align="right" class="text"><a href="javascript: checkAll( event_form, 'mark_eids' );">Check All</a></td>
</tr>
<tr><td colspan="5" class="text">&nbsp;</td></tr>
<tr><td colspan="5"><table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr align="center">
<td width="4%" class="text"><a href="<?php echo $PHP_SELF ?>?view=events&mid=<?php echo $mid ?>&max_events=<?php echo $max_events ?>&archived=<?php echo $archived ?>&period=<?php echo $period ?>&sort_field=Id&sort_asc=<?php echo $sort_field == 'Id'?!$sort_asc:0 ?>">Id<?php if ( $sort_field == "Id" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td width="24%" class="text"><a href="<?php echo $PHP_SELF ?>?view=events&mid=<?php echo $mid ?>&max_events=<?php echo $max_events ?>&archived=<?php echo $archived ?>&period=<?php echo $period ?>&sort_field=Name&sort_asc=<?php echo $sort_field == 'Name'?!$sort_asc:0 ?>">Name<?php if ( $sort_field == "Name" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?php echo $PHP_SELF ?>?view=events&mid=<?php echo $mid ?>&max_events=<?php echo $max_events ?>&archived=<?php echo $archived ?>&period=<?php echo $period ?>&sort_field=Time&sort_asc=<?php echo $sort_field == 'Time'?!$sort_asc:0 ?>">Time<?php if ( $sort_field == "Time" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?php echo $PHP_SELF ?>?view=events&mid=<?php echo $mid ?>&max_events=<?php echo $max_events ?>&archived=<?php echo $archived ?>&period=<?php echo $period ?>&sort_field=Secs&sort_asc=<?php echo $sort_field == 'Secs'?!$sort_asc:0 ?>">Secs<?php if ( $sort_field == "Secs" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?php echo $PHP_SELF ?>?view=events&mid=<?php echo $mid ?>&max_events=<?php echo $max_events ?>&archived=<?php echo $archived ?>&period=<?php echo $period ?>&sort_field=Frames&sort_asc=<?php echo $sort_field == 'Frames'?!$sort_asc:0 ?>">Frames<?php if ( $sort_field == "Frames" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?php echo $PHP_SELF ?>?view=events&mid=<?php echo $mid ?>&max_events=<?php echo $max_events ?>&archived=<?php echo $archived ?>&period=<?php echo $period ?>&sort_field=Score&sort_asc=<?php echo $sort_field == 'Score'?!$sort_asc:0 ?>">Score<?php if ( $sort_field == "Score" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
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
<td align="center" class="text"><input type="checkbox" name="mark_eids[]" value="<?php echo $row[Id] ?>"></td>
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
	$result = mysql_query( "select E.*,M.Name as MonitorName,M.Width,M.Height from Events as E, Monitors as M where E.Id = '$eid' and E.MonitorId = M.Id" );
	if ( !$result )
		die( mysql_error() );
	$event = mysql_fetch_assoc( $result );

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
function deleteEvent() {
	opener.location.href = "<?php echo $PHP_SELF ?>?view=none&action=delete&mark_eid=<?php echo $eid ?>";
	window.close();
}
</script>
</head>
<body>
<table border="0">
<tr><td colspan="2" class="text"><b>Image <?php echo $eid."-".$fid." ($frame[Score])" ?></b></td>
<td align="center" class="text"><a href="javascript: deleteEvent();">Delete</a></td>
<td align="right" class="text"><a href="javascript: closeWindow();">Close</a></td>
</tr>
<tr><td colspan="4"><img src="<?php echo $image_path ?>" width="<?php echo $event[Width] ?>" height="<?php echo $event[Height] ?>" border="0"></td></tr>
<tr>
<?php if ( $fid > 1 ) { ?>
<td align="center" width="25%" class="text"><a href="<?php echo $PHP_SELF ?>?view=image&eid=<?php echo $eid ?>&fid=<?php echo $first_fid ?>">First</a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } if ( $fid > 1 ) { ?>
<td align="center" width="25%" class="text"><a href="<?php echo $PHP_SELF ?>?view=image&eid=<?php echo $eid ?>&fid=<?php echo $prev_fid ?>">Prev</a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } if ( $fid < $max_fid ) { ?>
<td align="center" width="25%" class="text"><a href="<?php echo $PHP_SELF ?>?view=image&eid=<?php echo $eid ?>&fid=<?php echo $next_fid ?>">Next</a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } if ( $fid < $max_fid ) { ?>
<td align="center" width="25%" class="text"><a href="<?php echo $PHP_SELF ?>?view=image&eid=<?php echo $eid ?>&fid=<?php echo $last_fid ?>">Last</a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
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
<form method="get" action="<?php echo $PHP_SELF ?>">
<input type="hidden" name="view" value="<?php echo $view ?>">
<input type="hidden" name="action" value="rename">
<input type="hidden" name="eid" value="<?php echo $eid ?>">
<input type="text" size="16" name="event_name" value="<?php echo $event[Name] ?>" class="form">
<input type="submit" value="Rename" class="form"></td>
</tr>
<tr>
<td align="center" class="text"><a href="javascript: refreshWindow();">Refresh</a></td>
<td align="center" class="text"><a href="<?php echo $PHP_SELF ?>?view=none&action=delete&mark_eid=<?php echo $eid ?>">Delete</a></td>
<?php if ( $event[Archived] ) { ?>
<td align="center" class="text"><a href="<?php echo $PHP_SELF ?>?view=<?php echo $view ?>&action=unarchive&mid=<?php echo $event[MonitorName] ?>&eid=<?php echo $eid ?>">Unarchive</a></td>
<?php } else { ?>
<td align="center" class="text"><a href="<?php echo $PHP_SELF ?>?view=<?php echo $view ?>&action=archive&mid=<?php echo $event[MonitorName] ?>&eid=<?php echo $eid ?>">Archive</a></td>
<?php } ?>
<?php if ( $mode == "stream" ) { ?>
<td align="center" class="text"><a href="<?php echo $PHP_SELF ?>?view=event&mode=still&mid=<?php echo $mid ?>&eid=<?php echo $eid ?>">Stills</a></td>
<?php } elseif ( canStream() ) { ?>
<td align="center" class="text"><a href="<?php echo $PHP_SELF ?>?view=event&mode=stream&mid=<?php echo $mid ?>&eid=<?php echo $eid ?>">Stream</a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
<?php if ( MPEG_ENCODE_PATH && file_exists( MPEG_ENCODE_PATH ) ) { ?>
<td align="center" class="text"><a href="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=video&eid=<?php echo $eid ?>', 'zmVideo', 100, 80 );">Video</a></td>
<?php } else { ?>
<td align="center" class="text">&nbsp;</td>
<?php } ?>
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
		if ( $scale == 1 || !file_exists( NETPBM_DIR."/jpegtopnm" ) )
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
					$command = NETPBM_DIR."/jpegtopnm -dct fast $anal_image | ".NETPBM_DIR."/pnmscalefixed $fraction | ".NETPBM_DIR."/ppmtojpeg --dct=fast > $thumb_image";
				else
					$command = NETPBM_DIR."/jpegtopnm -dct fast $capt_image | ".NETPBM_DIR."/pnmscalefixed $fraction | ".NETPBM_DIR."/ppmtojpeg --dct=fast > $thumb_image";
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
<tr><td colspan="3" align="center"><img src="<?php echo $image ?>" usemap="#zonemap" width="<?php echo $monitor[Width] ?>" height="<?php echo $monitor[Height] ?>" border="0"></td></tr>
</table>
<table align="center" border="0" cellspacing="0" cellpadding="0" width="96%">
<form name="event_form" method="get" action="<?php echo $PHP_SELF ?>">
<input type="hidden" name="view" value="<?php echo $view ?>">
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
<td align="center" class="text"><input type="checkbox" name="mark_zids[]" value="<?php echo $zone[Id] ?>"></td>
</tr>
<?php
	}
?>
<tr>
<td align="center" class="text">&nbsp;</td>
<td colspan="4" align="center"><input type="button" value="Add New Zone" class="form" onClick="javascript: newWindow( '<?php echo $PHP_SELF ?>?view=zone&mid=<?php echo $mid ?>&zid=-1', 'zmZone', 360, 480 );"></td>
<td align="center"><input type="submit" value="Delete" class="form"></td>
</tr>
</form>
</table>
</body>
</html>
<?php
}
elseif( $view == "monitor" )
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
	}
?>
<html>
<head>
<title>ZM - Monitor <?php echo $monitor[Name] ?></title>
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
function validateForm(theForm)
{
	return( true );
}

function closeWindow() {
        window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td colspan="2" align="left" class="head">Monitor <?php echo $monitor[Name] ?></td>
</tr>
<form name="monitorForm" method="get" action="<?php echo $PHP_SELF ?>" onsubmit="return validateForm(this)">
<input type="hidden" name="view" value="<?php echo $view ?>">
<input type="hidden" name="action" value="monitor">
<input type="hidden" name="mid" value="<?php echo $mid ?>">
<tr>
<td align="left" class="smallhead">Parameter</td><td align="left" class="smallhead">Value</td>
</tr>
<tr><td align="left" class="text">Name</td><td align="left" class="text"><input type="text" name="new_name" value="<?php echo $monitor[Name] ?>" size="12" class="form"></td></tr>
<tr><td align="left" class="text">Function</td><td align="left" class="text"><select name="new_function" class="form">
<?php
	foreach ( getEnumValues( 'Monitors', 'Function' ) as $opt_function )
	{
?>
<option value="<?php echo $opt_function ?>"<?php if ( $opt_function == $monitor['Function'] ) { ?> selected<?php } ?>><?php echo $opt_function ?></option>
<?php
	}
?>
</select></td></tr>
<tr><td align="left" class="text">Device Number (/dev/video?)</td><td align="left" class="text"><input type="text" name="new_device" value="<?php echo $monitor[Device] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Device Channel</td><td align="left" class="text"><input type="text" name="new_channel" value="<?php echo $monitor[Channel] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Device Format (1=PAL,2=NTSC etc)</td><td align="left" class="text"><input type="text" name="new_format" value="<?php echo $monitor[Format] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Device Width (pixels)</td><td align="left" class="text"><input type="text" name="new_width" value="<?php echo $monitor[Width] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Device Height (pixels)</td><td align="left" class="text"><input type="text" name="new_height" value="<?php echo $monitor[Height] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Device Colour Depth</td><td align="left" class="text"><input type="text" name="new_colours" value="<?php echo $monitor[Colours] ?>" size="4" class="form"></td></tr>
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
elseif( $view == "zone" )
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
<title>ZM - <?php echo $monitor[Name] ?> - Zone <?php echo $zone[Name] ?></title>
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
	else
	{
		theForm.new_alarm_rgb_r.disabled = false;
		theForm.new_alarm_rgb_r.value = "<?php echo ($zone[AlarmRGB]>>16)&0xff; ?>";
		theForm.new_alarm_rgb_g.disabled = false;
		theForm.new_alarm_rgb_g.value = "<?php echo ($zone[AlarmRGB]>>8)&0xff; ?>";
		theForm.new_alarm_rgb_b.disabled = false;
		theForm.new_alarm_rgb_b.value = "<?php echo $zone[AlarmRGB]&0xff; ?>";
		theForm.new_alarm_threshold.disabled = false;
		theForm.new_alarm_threshold.value = "<?php echo $zone[AlarmThreshold] ?>";
		theForm.new_min_alarm_pixels.disabled = false;
		theForm.new_min_alarm_pixels.value = "<?php echo $zone[MinAlarmPixels] ?>";
		theForm.new_max_alarm_pixels.disabled = false;
		theForm.new_max_alarm_pixels.value = "<?php echo $zone[MaxAlarmPixels] ?>";
		theForm.new_filter_x.disabled = false;
		theForm.new_filter_x.value = "<?php echo $zone[FilterX] ?>";
		theForm.new_filter_y.disabled = false;
		theForm.new_filter_y.value = "<?php echo $zone[FilterY] ?>";
		theForm.new_min_filter_pixels.disabled = false;
		theForm.new_min_filter_pixels.value = "<?php echo $zone[MinFilterPixels] ?>";
		theForm.new_max_filter_pixels.disabled = false;
		theForm.new_max_filter_pixels.value = "<?php echo $zone[MaxFilterPixels] ?>";
		theForm.new_min_blob_pixels.disabled = false;
		theForm.new_min_blob_pixels.value = "<?php echo $zone[MinBlobPixels] ?>";
		theForm.new_max_blob_pixels.disabled = false;
		theForm.new_max_blob_pixels.value = "<?php echo $zone[MaxBlobPixels] ?>";
		theForm.new_min_blobs.disabled = false;
		theForm.new_min_blobs.value = "<?php echo $zone[MinBlobs] ?>";
		theForm.new_max_blobs.disabled = false;
		theForm.new_max_blobs.value = "<?php echo $zone[MaxBlobs] ?>";
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
	var max_width = <?php echo $monitor[Width]-1 ?>;
	var max_height = <?php echo $monitor[Height]-1 ?>;
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
	if ( document.zoneForm.new_units.value == "Percent" )
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
	return( checkBounds( theField, fieldText, 0, <?php echo $monitor[Width]-1 ?> ) );
}

function checkHeight(theField,fieldText)
{
	return( checkBounds( theField, fieldText, 0, <?php echo $monitor[Height]-1 ?> ) );
}

function checkArea(theField,fieldText)
{
	return( checkBounds( theField, fieldText, 0, <?php echo $monitor[Width]*$monitor[Height] ?> ) );
}

function closeWindow() {
        window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td colspan="2" align="left" class="head">Monitor <?php echo $monitor[Name] ?> - Zone <?php echo $zone[Name] ?></td>
</tr>
<form name="zoneForm" method="get" action="<?php echo $PHP_SELF ?>" onsubmit="return validateForm(this)">
<input type="hidden" name="view" value="<?php echo $view ?>">
<input type="hidden" name="action" value="zone">
<input type="hidden" name="mid" value="<?php echo $mid ?>">
<input type="hidden" name="zid" value="<?php echo $zid ?>">
<input type="hidden" name="new_alarm_rgb" value="">
<tr>
<td align="left" class="smallhead">Parameter</td><td align="left" class="smallhead">Value</td>
</tr>
<tr><td align="left" class="text">Name</td><td align="left" class="text"><input type="text" name="new_name" value="<?php echo $zone[Name] ?>" size="12" class="form"></td></tr>
<tr><td align="left" class="text">Type</td><td align="left" class="text"><select name="new_type" class="form" onchange="applyZoneType(zoneForm)">
<?php
	foreach ( getEnumValues( 'Zones', 'Type' ) as $opt_type )
	{
?>
<option value="<?php echo $opt_type ?>"<?php if ( $opt_type == $zone['Type'] ) { ?> selected<?php } ?>><?php echo $opt_type ?></option>
<?php
	}
?>
</select></td></tr>
<tr><td align="left" class="text">Units</td><td align="left" class="text"><select name="new_units" class="form" onchange="applyZoneUnits(zoneForm)">
<?php
	foreach ( getEnumValues( 'Zones', 'Units' ) as $opt_units )
	{
?>
<option value="<?php echo $opt_units ?>"<?php if ( $opt_units == $zone['Units'] ) { ?> selected<?php } ?>><?php echo $opt_units ?></option>
<?php
	}
?>
</select></td></tr>
<tr><td align="left" class="text">Minimum X (left)</td><td align="left" class="text"><input type="text" name="new_lo_x" value="<?php echo $zone[LoX] ?>" size="4" class="form" onchange="checkWidth(this,'Minimum X')"></td></tr>
<tr><td align="left" class="text">Minimum Y (top)</td><td align="left" class="text"><input type="text" name="new_lo_y" value="<?php echo $zone[LoY] ?>" size="4" class="form" onchange="checkHeight(this,'Minimum Y')"></td></tr>
<tr><td align="left" class="text">Maximum X (right)</td><td align="left" class="text"><input type="text" name="new_hi_x" value="<?php echo $zone[HiX] ?>" size="4" class="form" onchange="checkWidth(this,'Maximum X')"></td></tr>
<tr><td align="left" class="text">Maximum Y (bottom)</td><td align="left" class="text"><input type="text" name="new_hi_y" value="<?php echo $zone[HiY] ?>" size="4" class="form" onchange="checkHeight(this,'Maximum Y')"></td></tr>
<tr><td align="left" class="text">Alarm Colour (RGB)</td><td align="left" class="text">R:<input type="text" name="new_alarm_rgb_r" value="<?php echo ($zone[AlarmRGB]>>16)&0xff ?>" size="3" class="form">&nbsp;G:<input type="text" name="new_alarm_rgb_g" value="<?php echo ($zone[AlarmRGB]>>8)&0xff ?>" size="3" class="form">&nbsp;B:<input type="text" name="new_alarm_rgb_b" value="<?php echo $zone[AlarmRGB]&0xff ?>" size="3" class="form"></td></tr>
<tr><td align="left" class="text">Alarm Threshold (0>=?<=255)</td><td align="left" class="text"><input type="text" name="new_alarm_threshold" value="<?php echo $zone[AlarmThreshold] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Minimum Alarmed Area</td><td align="left" class="text"><input type="text" name="new_min_alarm_pixels" value="<?php echo $zone[MinAlarmPixels] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Alarmed Area')"></td></tr>
<tr><td align="left" class="text">Maximum Alarmed Area</td><td align="left" class="text"><input type="text" name="new_max_alarm_pixels" value="<?php echo $zone[MaxAlarmPixels] ?>" size="6" class="form" onchange="checkArea(this,'Maximum Alarmed Area')"></td></tr>
<tr><td align="left" class="text">Filter Width (pixels)</td><td align="left" class="text"><input type="text" name="new_filter_x" value="<?php echo $zone[FilterX] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Filter Height (pixels)</td><td align="left" class="text"><input type="text" name="new_filter_y" value="<?php echo $zone[FilterY] ?>" size="4" class="form"></td></tr>
<tr><td align="left" class="text">Minimum Filtered Area</td><td align="left" class="text"><input type="text" name="new_min_filter_pixels" value="<?php echo $zone[MinFilterPixels] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Filtered Area')"></td></tr>
<tr><td align="left" class="text">Maximum Filtered Area</td><td align="left" class="text"><input type="text" name="new_max_filter_pixels" value="<?php echo $zone[MaxFilterPixels] ?>" size="6" class="form" onchange="checkArea(this,'Minimum Filtered Area')"></td></tr>
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

	$event_dir = EVENT_PATH."/$event[MonitorName]/".sprintf( "%04d", $eid );
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
			fputs( $fp, "INPUT_CONVERT	".NETPBM_DIR."/jpegtopnm * | ".NETPBM_DIR."/pgmtoppm white | ".NETPBM_DIR."/ppmtojpeg\n" );
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

		exec( MPEG_ENCODE_PATH." $param_file >$event_dir/mpeg.log" );
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
<form method="get" action="<?php echo $PHP_SELF ?>">
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
<form method="get" action="<?php echo $PHP_SELF ?>">
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
<?php
if ( $refresh_parent )
{
?>
opener.location.reload();
<?php
}
?>
window.close();
</script>
</head>
</html>
<?php
}
?>
