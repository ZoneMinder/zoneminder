<?php
	if ( !canView( 'Events' ) )
	{
		$view = "error";
		return;
	}
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

	if ( !isset( $rate ) )
		$rate = 1;
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
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="eid" value="<?= $eid ?>">
<input type="text" size="16" name="event_name" value="<?= $event[Name] ?>" class="form">
<input type="submit" value="Rename" class="form"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>></form></td>
<td colspan="2" align="right" class="text">
<form name="learn_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="learn">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="eid" value="<?= $eid ?>">
<input type="hidden" name="mark_eid" value="<?= $eid ?>">
<?php if ( LEARN_MODE ) { ?>
Learn Pref:&nbsp;<select name="learn_state" class="form" onChange="learn_form.submit();"><option value=""<?php if ( !$event[LearnState] ) echo " selected" ?>>Ignore</option><option value="-"<?php if ( $event[LearnState]=='-' ) echo " selected" ?>>Exclude</option><option value="+"<?php if ( $event[LearnState]=='+' ) echo " selected" ?>>Include</option></select>
<?php } ?>
</form></td>
<td colspan="1" align="right" class="text">
<form name="rate_form" method="get" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="rename">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="eid" value="<?= $eid ?>">
<?php buildSelect( "rate", $rates, "document.rate_form.submit();" ); ?>
</form>
</td>
</tr>
<tr>
<td align="center" class="text"><a href="javascript: refreshWindow();">Refresh</a></td>
<td align="center" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="<?= $PHP_SELF ?>?view=none&action=delete&mid=<?= $mid ?>&mark_eid=<?= $eid ?>">Delete</a><?php } else { ?>&nbsp;<?php } ?></td>
<?php if ( $event[Archived] ) { ?>
<td align="center" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&action=unarchive&mid=<?= $mid ?>&eid=<?= $eid ?>">Unarchive</a><?php } else { ?>&nbsp;<?php } ?></td>
<?php } else { ?>
<td align="center" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&action=archive&mid=<?= $mid ?>&eid=<?= $eid ?>">Archive</a><?php } else { ?>&nbsp;<?php } ?></td>
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
		$stream_src = ZM_PATH_ZMS."?path=".ZM_PATH_WEB."&event=$eid&rate=$rate";
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
<td width="25%" align="center" class="text"><?php if ( canEdit( 'Events' ) && $prev_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&mid=<?= $mid ?>&eid=<?= $prev_event[Id] ?>&action=delete&mid=<?= $mid ?>&mark_eid=<?= $eid ?>">Delete & Prev</a><?php } else { ?>&nbsp;<?php } ?></td>
<td width="25%" align="center" class="text"><?php if ( canEdit( 'Events' ) && $next_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&mid=<?= $mid ?>&eid=<?= $next_event[Id] ?>&action=delete&mid=<?= $mid ?>&mark_eid=<?= $eid ?>">Delete & Next</a><?php } else { ?>&nbsp;<?php } ?></td>
<td width="25%" align="center" class="text"><?php if ( $next_event ) { ?><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=<?= $mode ?>&mid=<?= $mid ?>&eid=<?= $next_event[Id] ?>">Next</a><?php } else { ?>&nbsp;<?php } ?></td>
</tr></table></td>
</tr>
</table>
</body>
</html>
