<?php
	if ( !canView( 'Events' ) )
	{
		$view = "error";
		return;
	}
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
					$dt_val = strftime( "%Y-%m-%d %H:%M:%S", strtotime( $$value_name ) );
					$filter_sql .= "E.StartTime ".$$op_name." '$dt_val'";
					$filter_query .= "&$op_name=".urlencode($$op_name);
					$filter_fields .= '<input type="hidden" name="'.$op_name.'" value="'.$$op_name.'">'."\n";
					break;
				case 'Date':
					$dt_val = strftime( "%Y-%m-%d %H:%M:%S", strtotime( $$value_name ) );
					$filter_sql .= "to_days( E.StartTime ) ".$$op_name." to_days( '$dt_val' )";
					$filter_query .= "&$op_name=".urlencode($$op_name);
					$filter_fields .= '<input type="hidden" name="'.$op_name.'" value="'.$$op_name.'">'."\n";
					break;
				case 'Time':
					$dt_val = strftime( "%Y-%m-%d %H:%M:%S", strtotime( $$value_name ) );
					$filter_sql .= "extract( hour_second from E.StartTime ) ".$$op_name." extract( hour_second from '$dt_val' )";
					$filter_query .= "&$op_name=".urlencode($$op_name);
					$filter_fields .= '<input type="hidden" name="'.$op_name.'" value="'.$$op_name.'">'."\n";
					break;
				case 'Weekday':
					$dt_val = strftime( "%Y-%m-%d %H:%M:%S", strtotime( $$value_name ) );
					$filter_sql .= "weekday( E.StartTime ) ".$$op_name." weekday( '$dt_val' )";
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
function checkAll(form,name)
{
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
<td align="right" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="javascript: checkAll( document.event_form, 'mark_eids' );">Check All</a><?php } else { ?>&nbsp;<?php } ?></td>
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
<td align="center" class="text"><input type="checkbox" name="mark_eids[]" value="<?= $row[Id] ?>" onClick="configureButton( document.event_form, 'mark_eids' );"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>></td>
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
