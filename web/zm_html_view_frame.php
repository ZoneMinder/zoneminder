<?php
	if ( !canView( 'Events' ) )
	{
		$view = "error";
		return;
	}
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
		$result = mysql_query( "select * from Frames where EventID = '$eid' and Score = '$event['MaxScore']'" );
		if ( !$result )
			die( mysql_error() );
		$frame = mysql_fetch_assoc( $result );
		$fid = $frame['FrameId'];
	}

	$result = mysql_query( "select count(*) as FrameCount from Frames where EventID = '$eid'" );
	if ( !$result )
		die( mysql_error() );
	$row = mysql_fetch_assoc( $result );
	$max_fid = $row['FrameCount'];

	$first_fid = 1;
	$prev_fid = $fid-1;
	$next_fid = $fid+1;
	$last_fid = $max_fid;

	$image_path = $frame['ImagePath'];
	$anal_image = preg_replace( "/capture/", "analyse", $image_path );
	if ( file_exists( $anal_image ) )
	{
		$image_path = $anal_image;
	}
	$img_class = $frame['AlarmFrame']?"alarm":"normal";
?>
<html>
<head>
<title>ZM - <?= $zmSlangFrame ?> <?= $eid."-".$fid ?></title>
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
	location.href = "<?= $PHP_SELF ?>?view=none&action=delete&mid=<?= $mid ?>&mark_eid=<?= $eid ?>";
	//window.close();
}
</script>
</head>
<body>
<table border="0">
<tr><td colspan="2" class="smallhead"><?= $zmSlangFrame ?> <?= $eid."-".$fid." ($frame['Score'])" ?>
<?php if ( ZM_RECORD_EVENT_STATS && $frame['AlarmFrame'] ) { ?>
(<a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=stats&eid=<?= $eid ?>&fid=<?= $fid ?>', 'zmStats', <?= $jws['stats']['w'] ?>, <?= $jws['stats']['h'] ?> );"><?= $zmSlangStats ?></a>)
<?php } ?>
</td>
<td align="center" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="javascript: deleteEvent();"><?= $zmSlangDelete ?></a><?php } else { ?>&nbsp<?php } ?></td>
<td align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
<tr><td colspan="4"><img src="<?= $image_path ?>" width="<?= $event['Width'] ?>" height="<?= $event['Height'] ?>" class="<?= $img_class ?>"></td></tr>
<tr>
<?php if ( $fid > 1 ) { ?>
<td align="center" width="25%" class="text"><a href="<?= $PHP_SELF ?>?view=image&eid=<?= $eid ?>&fid=<?= $first_fid ?>"><?= $zmSlangFirst ><</a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } if ( $fid > 1 ) { ?>
<td align="center" width="25%" class="text"><a href="<?= $PHP_SELF ?>?view=image&eid=<?= $eid ?>&fid=<?= $prev_fid ?>"><?= $zmSlangPrev ?></a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } if ( $fid < $max_fid ) { ?>
<td align="center" width="25%" class="text"><a href="<?= $PHP_SELF ?>?view=image&eid=<?= $eid ?>&fid=<?= $next_fid ?>"><?= $zmSlangNext ?></a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } if ( $fid < $max_fid ) { ?>
<td align="center" width="25%" class="text"><a href="<?= $PHP_SELF ?>?view=image&eid=<?= $eid ?>&fid=<?= $last_fid ?>"><?= $zmSlangLast ?></a></td>
<?php } else { ?>
<td align="center" width="25%" class="text">&nbsp;</td>
<?php } ?>
</tr>
</table>
</body>
</html>
