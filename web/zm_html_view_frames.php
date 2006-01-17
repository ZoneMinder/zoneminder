<?php
//
// ZoneMinder web frames view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
	$view = "error";
	return;
}
$sql = "select E.*,M.Name as MonitorName,M.Width,M.Height from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = '$eid'";
$result = mysql_query( $sql );
if ( !$result )
	die( mysql_error() );
$event = mysql_fetch_assoc( $result );
mysql_free_result( $result );

$sql = "select *, unix_timestamp( TimeStamp ) as UnixTimeStamp from Frames where EventID = '$eid' order by FrameId";
$result = mysql_query( $sql );
if ( !$result )
	die( mysql_error() );
while ( $row = mysql_fetch_assoc( $result ) )
{
	$frames[] = $row;
}
mysql_free_result( $result );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangFrames ?> <?= $eid ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
window.focus();
function newWindow(Url,Name,Width,Height)
{
   	var Win = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table width="96%" border="0">
<tr>
<td align="left" class="smallhead"><b><?= $zmSlangEvent ?> <?= $eid ?></b></td>
<td align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
<tr><td colspan="2"><table width="100%" border="0" bgcolor="#7F7FB2" cellpadding="3" cellspacing="1"><tr bgcolor="#FFFFFF">
<td class="smallhead" align="center"><?= $zmSlangFrameId ?></td>
<td class="smallhead" align="center"><?= $zmSlangAlarmFrame ?></td>
<td class="smallhead" align="center"><?= $zmSlangTimeStamp ?></td>
<td class="smallhead" align="center"><?= $zmSlangTimeDelta ?></td>
<td class="smallhead" align="center"><?= $zmSlangScore ?></td>
</tr>
<?php
if ( count($frames) )
{
	foreach ( $frames as $frame )
	{
		$alarm_frame = $frame['Type']=='Alarm';
		$bgcolor = $alarm_frame?'#FA8072':($frame['Type']=='Bulk'?'#CCCCCC':'#FFFFFF');
?>
<tr bgcolor="<?= $bgcolor ?>">
<td class="text" align="center"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frame&eid=<?= $eid ?>&fid=<?= $frame['FrameId'] ?>', 'zmImage', <?= $event['Width']+$jws['image']['w'] ?>, <?= $event['Height']+$jws['image']['h'] ?> );"><?= $frame['FrameId'] ?></a></td>
<td class="text" align="center"><?= $alarm_frame?$zmSlangYes:$zmSlangNo ?></td>
<td class="text" align="center"><?= date( "H:i:s", $frame['UnixTimeStamp'] ) ?></td>
<td class="text" align="center"><?= number_format( $frame['Delta'], 2 ) ?></td>
<?php
		if ( ZM_RECORD_EVENT_STATS && $alarm_frame )
		{
?>
<td class="text" align="center"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=stats&eid=<?= $eid ?>&fid=<?= $frame['FrameId'] ?>', 'zmStats', <?= $jws['stats']['w'] ?>, <?= $jws['stats']['h'] ?> );"><?= $frame['Score'] ?></a></td>
<?php
		}
		else
		{
?> 
<td class="text" align="center"><?= $frame['Score'] ?></td>
<?php
		}
?> 
</tr>
<?php
	}
}
else
{
?>
<tr bgcolor="#FFFFFF">
<td class="text" colspan="5" align="center"><br><?= $zmSlangNoFramesRecorded ?><br><br></td>
</tr>
<?php
}
?>
</table></td>
</tr>
</table>
</body>
</html>
