<?php
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
		if ( !visibleMonitor( $row[Id] ) )
		{
			continue;
		}
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
window.setTimeout( "window.location.replace('<?= $PHP_SELF ?>')", <?= ($start||$stop)?250:(REFRESH_MAIN*1000) ?> );
<?php
		}
?>
</script>
</head>
<body>
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<tr>
<td class="smallhead" align="left"><?= date( "D jS M, g:ia" ) ?></td>
<td class="bighead" align="center"><strong>ZoneMinder Console - <?= $status ?><?php if ( canEdit( 'System' ) ) { ?> (<a href="javascript: if ( confirmStatus( '<?= $new_status ?>' ) ) location='<?= $PHP_SELF ?>?<?= $new_status ?>=1';"><?= $new_status ?></a>) <?php } ?>- v<?= ZM_VERSION ?></strong></td>
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
	if ( canView( 'Stream' ) && $cycle_count > 1 )
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
<?php
	if ( ZM_OPT_USE_AUTH )
	{
?>
<td class="smallhead" align="center">Logged in as <a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=logout', 'zmLogout', <?= $jws['logout']['w'] ?>, <?= $jws['logout']['h'] ?>);"><?= $user[Username] ?></a>, configured for 
<?php
	}
	else
	{
?>
<td class="smallhead" align="center">Configured for 
<?php
	}
?>
<a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=bandwidth', 'zmBandwidth', <?= $jws['bandwidth']['w'] ?>, <?= $jws['bandwidth']['h'] ?>);"><?= $bandwidth ?></a> bandwidth</td>
<td class="smallhead" align="right"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=options', 'zmOptions', ".$jws[options][w].", ".$jws[options][h]." );", "Options", canView( 'System' ) ) ?></td>
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
<td align="center" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=monitor&mid=$monitor[Id]', 'zmMonitor', ".$jws[monitor][w].", ".$jws[monitor][h]." );", "$monitor[Id].", canView( 'Monitors' ) ) ?></td>
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
<td align="left" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=watch&mid=$monitor[Id]', 'zmWatch$monitor[Name]', ".($monitor[Width]+$jws['watch']['w']).", ".($monitor[Height]+$jws['watch']['h'])." );", $monitor[Name], canView( 'Stream' ) ) ?></td>
<td align="left" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=function&mid=$monitor[Id]', 'zmFunction', ".$jws['function']['w'].", ".$jws['function']['h']." );", "<span class=\"$fclass\">$monitor[Function]</span>", canEdit( 'Monitors' ) ) ?></td>
<?php if ( $monitor[Type] == "Local" ) { ?>
<td align="left" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=monitor&mid=$monitor[Id]', 'zmMonitor', ".$jws['monitor']['w'].", ".$jws['monitor']['h']." );", "<span class=\"$dclass\">/dev/video$monitor[Device] ($monitor[Channel])</span>", canEdit( 'Monitors' ) ) ?></td>
<?php } else { ?>
<td align="left" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=monitor&mid=$monitor[Id]', 'zmMonitor', ".$jws['monitor']['w'].", ".$jws['monitor']['h']." );", "<span class=\"$dclass\">$monitor[Host]</span>", canEdit( 'Monitors' ) ) ?></td>
<?php } ?>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&mid=$monitor[Id]&filter=1', 'zmEvents$monitor[Name]', ".$jws['events']['w'].", ".$jws['events']['h']." );", $monitor[EventCount], canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&mid=$monitor[Id]&filter=1&trms=2&attr1=Archived&val1=0&cnj2=and&attr2=DateTime&op2=%3e%3d&val2=last+hour', 'zmEvents$monitor[Name]', ".$jws['events']['w'].", ".$jws['events']['h']." );", $monitor[HourEventCount], canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&mid=$monitor[Id]&filter=1&trms=2&attr1=Archived&val1=0&cnj2=and&attr2=DateTime&op2=%3e%3d&val2=last+day', 'zmEvents$monitor[Name]', ".$jws['events']['w'].", ".$jws['events']['h']." );", $monitor[DayEventCount], canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&mid=$monitor[Id]&filter=1&trms=2&attr1=Archived&val1=0&cnj2=and&attr2=DateTime&op2=%3e%3d&val2=last+week', 'zmEvents$monitor[Name]', ".$jws['events']['w'].", ".$jws['events']['h']." );", $monitor[WeekEventCount], canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&mid=$monitor[Id]&filter=1&trms=2&attr1=Archived&val1=0&cnj2=and&attr2=DateTime&op2=%3e%3d&val2=last+month', 'zmEvents$monitor[Name]', ".$jws['events']['w'].", ".$jws['events']['h']." );", $monitor[MonthEventCount], canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=events&mid=$monitor[Id]&filter=1&trms=1&attr1=Archived&val1=1', 'zmEvents$monitor[Name]', ".$jws['events']['w'].", ".$jws['events']['h']." );", $monitor[ArchEventCount], canView( 'Events' ) ) ?></td>
<td align="right" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=zones&mid=$monitor[Id]', 'zmZones', ".($monitor[Width]+$jws['zones']['w']).", ".($monitor[Height]+$jws['zones']['h'])." );", $monitor[ZoneCount], canView( 'Monitors' ) ) ?></td>
<td align="center" class="text"><input type="checkbox" name="mark_mids[]" value="<?= $monitor[Id] ?>" onClick="configureButton( document.monitor_form, 'mark_mids' );"<?php if ( !canEdit( 'Monitors' ) || $user[MonitorIds] ) {?> disabled<?php } ?>></td>
</tr>
<?php
	}
?>
<tr>
<td colspan="2" align="center">
<input type="button" value="Refresh" class="form" onClick="javascript: location.reload(true);">
</td>
<td colspan="2" align="center">
<input type="button" value="Add New Monitor" class="form" onClick="javascript: newWindow( '<?= $PHP_SELF ?>?view=monitor&zid=-1', 'zmMonitor', <?= $jws['monitor']['w'] ?>, <?= $jws['monitor']['h'] ?>);"<?php if ( !canEdit( 'Monitors' ) || $user[MonitorIds] ) {?> disabled<?php } ?>>
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
