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

	ob_start();

	// Note this all has a bunch of extra padding as IE won't flush less than 1024 chars
?>
<html>
<head>
<title>ZM - Video - <?= $event[Name] ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
<tr>
<td align="center" class="head">Generating Video</td>
</tr>
<tr><td>&nbsp;</td></tr>
<tr><td>&nbsp;</td></tr>
</table>
</body>
<?php
	$buffer_string = "<!-- This is some long buffer text to ensure that IE flushes correctly -->";
	for ( $i = 0; $i < 4096/strlen($buffer_string); $i++ )
	{
		echo $buffer_string."\n";
	}
?>
</html>
<?php
	ob_end_flush();
	if ( $video_file = createVideo( $event ) )
	{
		$event_dir = ZM_DIR_EVENTS."/$event[MonitorName]/".sprintf( "%d", $eid );
		$video_path = $event_dir.'/'.$video_file;
		//header("Location: $video_path" );
?>
<html>
<head>
<title>ZM - Video - <?= $event[Name] ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
location.replace('<?= $video_path ?>');
</script>
</head>
</html>
<?php
	}
	else
	{
?>
<html>
<head>
<title>ZM - Video - <?= $event[Name] ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
</head>
<body>
<p class="head" align="center"><font color="red"><br><br><br>Video Generation Failed!<br><br><br></font></p>
</body>
</html>
<?php
	}
?>
