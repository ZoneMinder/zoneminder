<?php
	if ( !canView( 'Events' ) )
	{
		$view = "error";
		return;
	}
	$result = mysql_query( "select E.*,M.Name as MonitorName, M.Palette from Events as E, Monitors as M where E.Id = '$eid' and E.MonitorId = M.Id" );
	if ( !$result )
		die( mysql_error() );
	$event = mysql_fetch_assoc( $result );

	if ( !isset( $scale ) )
		$scale = 1;
	if ( !isset( $rate ) )
		$rate = 1;

	ob_start();
?>
<html>
<head>
<title>ZM - <?= $zmSlangVideo ?> - <?= $event['Name'] ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
</head>
<body>
<form name="video_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="<?= $action ?>">
<input type="hidden" name="eid" value="<?= $eid ?>">
<input type="hidden" name="generate" value="1">
<table align="center" border="0" cellspacing="0" cellpadding="2" width="96%">
<tr><td width="50">&nbsp;</td><td class="head" align="center"><?= $zmSlangVideoGenParms ?></td><td width="50" class="text" align="right"><a href="javascript: window.close();"><?= $zmSlangClose ?></a></tr>
</table>
<table align="center" border="0" cellspacing="0" cellpadding="2" width="96%">
<tr><td width="50%">&nbsp;</td><td width="50%">&nbsp;</td></tr>
<tr><td class="text" align="right"><?= $zmSlangFrameRate ?></td><td><?= buildSelect( "rate", $rates ) ?></td></tr>
<tr><td class="text" align="right"><?= $zmSlangVideoSize ?></td><td><?= buildSelect( "scale", $scales ) ?></td></tr>
<tr><td class="text" align="right"><?= $zmSlangOverwriteExisting ?></td><td><input type="checkbox" class="form-noborder" name="overwrite" value="1"<?php if ( $overwrite ) { ?> checked<?php } ?>></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2" align="center"><input type="submit" class="form" value="<?= $zmSlangGenerateVideo ?>"></td></tr>
</table>
</form>
<?php
	if ( $generate )
	{
?>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr><td>&nbsp;</td></tr>
<tr>
<td align="center" class="head"><?= $zmSlangGeneratingVideo ?></td>
</tr>
<tr><td>&nbsp;</td></tr>
</table>
</body>
</html>
<?php
		$buffer_string = "<!-- This is some long buffer text to ensure that IE flushes correctly -->";
		for ( $i = 0; $i < 4096/strlen($buffer_string); $i++ )
		{
			echo $buffer_string."\n";
		}
?>
<?php
		ob_end_flush();
		if ( $video_file = createVideo( $event, $rate, $scale, $overwrite ) )
		{
			$event_dir = ZM_DIR_EVENTS."/$event['MonitorName']/".sprintf( "%d", $eid );
			$video_path = $event_dir.'/'.$video_file;
?>
<html>
<head>
<script language="JavaScript">
location.replace('<?= $video_path ?>');
</script>
</head>
</html>
<?php
		}
		else
		{
			ob_end_flush();
?>
<html>
<head>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
</head>
<body>
<p class="head" align="center"><font color="red"><br><br><?= $zmSlangVideoGenFailed ?><br><br></font></p>
<?php
		}
	}
	else
	{
		ob_end_flush();
	}
?>
</body>
</html>
