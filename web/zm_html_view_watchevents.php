<?php
	if ( !canView( 'Events' ) )
	{
		$view = "error";
		return;
	}
	if ( empty($sort_field) )
	{
		$sort_field = "Time";
		$sort_asc = false;
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
		case 'Score' :
			$sort_column = "E.AvgScore";
			break;
		default:
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

	$result = mysql_query( "select * from Monitors where Id = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$monitor = mysql_fetch_assoc( $result );

?>
<html>
<head>
<title>ZM - <?= $monitor['Name'] ?> - <?= $zmSlangEvents ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function newWindow(Url,Name,Width,Height)
{
   	var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function eventWindow(Url,Name)
{
	var Name = window.open(Url,Name,"resizable,width=<?= $monitor['Width']+$jws['event']['w'] ?>,height=<?= $monitor['Height']+$jws['event']['h'] ?>");
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
	$sql = "select E.Id,E.Name,E.StartTime,E.Length,E.Frames,E.AlarmFrames,E.AvgScore,E.MaxScore from Monitors as M left join Events as E on M.Id = E.MonitorId where M.Id = '$mid' and E.Archived = 0";
	$sql .= " order by $sort_column $sort_order";
	$sql .= " limit 0,$max_events";
	$result = mysql_query( $sql );
	if ( !$result )
	{
		die( mysql_error() );
	}
	$n_events = mysql_num_rows( $result );
?>
<tr>
<td class="text"><b><?= sprintf( $zmClangLastEvents, $n_events, strtolower( zmVlang( $zmVlangEvent, $n_events ) ) ) ?></b></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=events&mid=<?= $monitor['Id'] ?>&page=1&filter=1&trms=0', 'zmEvents<?= $monitor['Name'] ?>', <?= $jws['events']['w'] ?>, <?= $jws['events']['h'] ?> );"><?= $zmSlangAll ?></a></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=events&mid=<?= $monitor['Id'] ?>&page=1&filter=1&trms=1&attr1=Archived&val1=1', 'zmEvents<?= $monitor['Name'] ?>', <?= $jws['events']['w'] ?>, <?= $jws['events']['h'] ?> );"><?= $zmSlangArchive ?></a></td>
<td align="right" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="javascript: checkAll( document.event_form, 'mark_eids' );"><?= $zmSlangCheckAll ?></a><?php } else { ?>&nbsp;<?php } ?></td>
</tr>
<tr><td colspan="5" class="text">&nbsp;</td></tr>
<tr><td colspan="5"><table border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="#7F7FB2">
<tr align="center" bgcolor="#FFFFFF">
<td width="4%" class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Id&sort_asc=<?= $sort_field == 'Id'?!$sort_asc:0 ?>"><?= $zmSlangId ?><?php if ( $sort_field == "Id" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td width="24%" class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Name&sort_asc=<?= $sort_field == 'Name'?!$sort_asc:0 ?>"><?= $zmSlangName ?><?php if ( $sort_field == "Name" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Time&sort_asc=<?= $sort_field == 'Time'?!$sort_asc:0 ?>"><?= $zmSlangTime ?><?php if ( $sort_field == "Time" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Secs&sort_asc=<?= $sort_field == 'Secs'?!$sort_asc:0 ?>"><?= $zmSlangSecs ?><?php if ( $sort_field == "Secs" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Frames&sort_asc=<?= $sort_field == 'Frames'?!$sort_asc:0 ?>"><?= $zmSlangFrames ?><?php if ( $sort_field == "Frames" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Score&sort_asc=<?= $sort_field == 'Score'?!$sort_asc:0 ?>"><?= $zmSlangScore ?><?php if ( $sort_field == "Score" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><?= $zmSlangMark ?></td>
</tr>
<?php
	while( $event = mysql_fetch_assoc( $result ) )
	{
?>
<tr bgcolor="#FFFFFF">
<td align="center" class="text"><a href="javascript: eventWindow( '<?= $PHP_SELF ?>?view=event&mid=<?= $mid ?>&eid=<?= $event['Id'] ?>&page=1', 'zmEvent' );"><?= $event['Id'] ?></a></td>
<td align="center" class="text"><a href="javascript: eventWindow( '<?= $PHP_SELF ?>?view=event&mid=<?= $mid ?>&eid=<?= $event['Id'] ?>&page=1', 'zmEvent' );"><?= $event['Name'] ?></a></td>
<td align="center" class="text"><?= strftime( "%m/%d %H:%M:%S", strtotime($event['StartTime']) ) ?></td>
<td align="center" class="text"><?= $event['Length'] ?></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frames&mid=<?= $mid ?>&eid=<?= $event['Id'] ?>', 'zmFrames', <?= $jws['frames']['w'] ?>, <?= $jws['frames']['h'] ?> );"><?= $event['Frames'] ?>/<?= $event['AlarmFrames'] ?></a></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frame&eid=<?= $event['Id'] ?>&fid=0', 'zmImage', <?= $monitor['Width']+$jws['image']['w'] ?>, <?= $monitor['Height']+$jws['image']['h'] ?> );"><?= $event['AvgScore'] ?>/<?= $event['MaxScore'] ?></a></td>
<td align="center" class="text"><input type="checkbox" name="mark_eids[]" value="<?= $event['Id'] ?>" onClick="configureButton( document.event_form, 'mark_eids' );"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>></td>
</tr>
<?php
	}
?>
</table></td></tr>
</table></td>
</tr>
<tr><td align="right"><input type="submit" name="delete_btn" value="<?= $zmSlangDelete ?>" class="form" disabled></td></tr>
</table></center>
</form>
</body>
</html>
