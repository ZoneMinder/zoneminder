<?php
	if ( !canView( 'Events' ) )
	{
		$view = "error";
		return;
	}
	$result = mysql_query( "select F.*,unix_timestamp(F.TimeStamp) as UnixTimeStamp,E.*,M.Name as MonitorName,M.Width,M.Height from Frames as F left join Events as E on F.EventId = E.Id left join Monitors as M on E.MonitorId = M.Id where F.EventId = '$eid' order by F.FrameId" );
	if ( !$result )
		die( mysql_error() );
	while ( $row = mysql_fetch_assoc( $result ) )
	{
		$frames[] = $row;
	}
?>
<html>
<head>
<title>ZM - <?= $zmSlangFrames ?> <?= $eid ?></title>
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
?>
<tr bgcolor="<?= $frame['AlarmFrame']?'#FA8072':'#FFFFFF' ?>">
<td class="text" align="center"><?= $frame['FrameId'] ?></td>
<td class="text" align="center"><?= $frame['AlarmFrame']?"yes":"no" ?></td>
<td class="text" align="center"><?= date( "H:i:s", $frame['UnixTimeStamp'] ) ?></td>
<td class="text" align="center"><?= number_format( $frame['Delta'], 2 ) ?></td>
<?php
			if ( ZM_RECORD_EVENT_STATS && $frame['AlarmFrame'] )
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
<td class="text" colspan="8" align="center"><br><?= $zmSlangNoFramesRecorded ?><br><br></td>
</tr>
<?php
	}
?>
</table></td>
</tr>
</table>
</body>
</html>
