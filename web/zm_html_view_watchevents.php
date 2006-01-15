<?php
//
// ZoneMinder web watch events view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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

if ( !canView( 'Events' ) )
{
	$view = "error";
	return;
}

$sort_field = "DateTime";
$sort_asc = false;

parseSort();

if ( !$max_events )
{
	$max_events = MAX_EVENTS;
}

if ( ZM_WEB_REFRESH_METHOD == "http" )
	header("Refresh: ".ZM_WEB_REFRESH_EVENTS."; URL=$PHP_SELF?view=watchevents&mid=$mid&max_events=".MAX_EVENTS );
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");			  // HTTP/1.0

$result = mysql_query( "select * from Monitors where Id = '$mid'" );
if ( !$result )
	die( mysql_error() );
$monitor = mysql_fetch_assoc( $result );
mysql_free_result( $result );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $monitor['Name'] ?> - <?= $zmSlangEvents ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
function newWindow(Url,Name,Width,Height)
{
   	var Win = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function eventWindow(Url,Name)
{
	var Win = window.open(Url,Name,"resizable,width=<?= $monitor['Width']+$jws['event']['w'] ?>,height=<?= $monitor['Height']+$jws['event']['h'] ?>");
}
function closeWindow()
{
	top.window.close();
}
function toggleCheck(element,name)
{
	var form = element.form;
	var checked = element.checked;
	for (var i = 0; i < form.elements.length; i++)
		if (form.elements[i].name.indexOf(name) == 0)
			form.elements[i].checked = checked;
	form.delete_btn.disabled = !checked;
}
function configureButton(element,name)
{
	var form = element.form;
	var checked = element.checked;
	if ( !checked )
	{
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
	}
	if ( !element.checked )
		form.toggle_check.checked = false;
	form.delete_btn.disabled = !checked;
}
<?php
if ( ZM_WEB_REFRESH_METHOD == "javascript" )
{
?>
window.setTimeout( "window.location.replace( '<?= "$PHP_SELF?view=watchevents&mid=$mid&max_events=".MAX_EVENTS ?>' )", <?= ZM_WEB_REFRESH_EVENTS*1000 ?> );
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
<td colspan="2" valign="top"><table border="0" cellspacing="0" cellpadding="0" width="100%">
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
<td width="30%"align="left" class="text"><b><?= sprintf( $zmClangLastEvents, $n_events, strtolower( zmVlang( $zmVlangEvent, $n_events ) ) ) ?></b></td>
<td align="center" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=events&page=1&filter=1&trms=1&attr1=MonitorId&op1=%3d&val1=".$monitor['Id']."', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $zmSlangAll, canView( 'Events' ) ) ?></td>
<td width="30%"align="right" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=events&page=1&filter=1&trms=2&attr1=MonitorId&op1=%3d&val1=".$monitor['Id']."&cnj2=and&attr2=Archived&val2=1', 'zmEvents', ".$jws['events']['w'].", ".$jws['events']['h']." );", $zmSlangArchive, canView( 'Events' ) ) ?></td>
</tr>
<tr><td colspan="3" class="text">&nbsp;</td></tr>
<tr><td colspan="3"><table border="0" cellspacing="0" cellpadding="0" width="100%" bgcolor="#7F7FB2">
<tr align="center" bgcolor="#FFFFFF">
<td width="4%" class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Id&sort_asc=<?= $sort_field == 'Id'?!$sort_asc:0 ?>"><?= $zmSlangId ?><?php if ( $sort_field == "Id" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td width="24%" class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Name&sort_asc=<?= $sort_field == 'Name'?!$sort_asc:0 ?>"><?= $zmSlangName ?><?php if ( $sort_field == "Name" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=StartTime&sort_asc=<?= $sort_field == 'StartTime'?!$sort_asc:0 ?>"><?= $zmSlangTime ?><?php if ( $sort_field == "StartTime" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Length&sort_asc=<?= $sort_field == 'Length'?!$sort_asc:0 ?>"><?= $zmSlangSecs ?><?php if ( $sort_field == "Length" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Frames&sort_asc=<?= $sort_field == 'Frames'?!$sort_asc:0 ?>"><?= $zmSlangFrames ?><?php if ( $sort_field == "Frames" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=watchevents&mid=<?= $mid ?>&max_events=<?= $max_events ?>&sort_field=Score&sort_asc=<?= $sort_field == 'Score'?!$sort_asc:0 ?>"><?= $zmSlangScore ?><?php if ( $sort_field == "Score" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><input type="checkbox" name="toggle_check" value="1" onClick="toggleCheck( this, 'mark_eids' );"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>></td>
</tr>
<?php
while( $event = mysql_fetch_assoc( $result ) )
{
?>
<tr bgcolor="#FFFFFF">
<td align="center" class="text"><a href="javascript: eventWindow( '<?= $PHP_SELF ?>?view=event&eid=<?= $event['Id'] ?>&trms=1&attr1=MonitorId&op1=%3d&val1=<?= $mid ?><?= $sort_query ?>&page=1', 'zmEvent' );"><?= $event['Id'] ?></a></td>
<td align="center" class="text"><a href="javascript: eventWindow( '<?= $PHP_SELF ?>?view=event&eid=<?= $event['Id'] ?>&trms=1&attr1=MonitorId&op1=%3d&val1=<?= $mid ?><?= $sort_query ?>&page=1', 'zmEvent' );"><?= $event['Name'] ?></a></td>
<td align="center" class="text"><?= strftime( "%m/%d %H:%M:%S", strtotime($event['StartTime']) ) ?></td>
<td align="center" class="text"><?= $event['Length'] ?></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frames&eid=<?= $event['Id'] ?>', 'zmFrames', <?= $jws['frames']['w'] ?>, <?= $jws['frames']['h'] ?> );"><?= $event['Frames'] ?>/<?= $event['AlarmFrames'] ?></a></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frame&eid=<?= $event['Id'] ?>&fid=0', 'zmImage', <?= $monitor['Width']+$jws['image']['w'] ?>, <?= $monitor['Height']+$jws['image']['h'] ?> );"><?= $event['AvgScore'] ?>/<?= $event['MaxScore'] ?></a></td>
<td align="center" class="text"><input type="checkbox" name="mark_eids[]" value="<?= $event['Id'] ?>" onClick="configureButton( this, 'mark_eids' );"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>></td>
</tr>
<?php
}
mysql_free_result( $result );
?>
</table></td></tr>
</table></td>
</tr>
<tr>
<td align="left"><input type="button" value="<?= $zmSlangZones ?>" class="form" onClick="javascript: newWindow( '<?= $PHP_SELF ?>?view=zones&mid=<?= $monitor['Id'] ?>', 'zmZones', <?= $monitor['Width']+$jws['zones']['w'] ?>, <?= $monitor['Height']+$jws['zones']['h'] ?> );"<?php if ( !canView( 'Monitors' ) ) { ?> disabled<?php } ?>></td>
<td align="right"><input type="submit" name="delete_btn" value="<?= $zmSlangDelete ?>" class="form" disabled></td>
</tr>
</table></center>
</form>
</body>
</html>
