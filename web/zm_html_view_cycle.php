<?php
	if ( !canView( 'Stream' ) )
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

	$result = mysql_query( "select * from Monitors where Function != 'None' order by Id" );
	$monitors = array();
	$mon_idx = 0;
	while( $row = mysql_fetch_assoc( $result ) )
	{
		if ( !visibleMonitor( $row[Id] ) )
		{
			continue;
		}
		if ( $mid && $row[Id] == $mid )
			$mon_idx = count($monitors);
		$monitors[] = $row;
	}

	$monitor = $monitors[$mon_idx];
	$next_mid = $mon_idx==(count($monitors)-1)?$monitors[0][Id]:$monitors[$mon_idx+1][Id];

	// Prompt an image to be generated
	chdir( ZM_DIR_IMAGES );
	$status = exec( escapeshellcmd( ZMU_COMMAND." -m $monitor[Id] -i" ) );
										 
	if ( ZM_WEB_REFRESH_METHOD == "http" )
		header("Refresh: ".REFRESH_CYCLE."; URL=$PHP_SELF?view=cycle&mid=$next_mid&mode=$mode" );
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
	header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");			  // HTTP/1.0
?>
<html>
<head>
<title>ZM - <?= $zmSlangCycleWatch ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function newWindow(Url,Name,Width,Height)
{
	var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function closeWindow()
{
	top.window.close();
}
<?php
	if ( ZM_WEB_REFRESH_METHOD == "javascript" )
	{
?>
window.setTimeout( "window.location.replace( '<?= "$PHP_SELF?view=cycle&mid=$next_mid&mode=$mode" ?>' )", <?= REFRESH_CYCLE*1000 ?> );
<?php
	}
?>
</script>
</head>
<body>
<table width="96%" align="center" border="0" cellspacing="0" cellpadding="4">
<tr>
<td width="33%" align="left" class="text"><b><?= $monitor[Name] ?></b></td>
<?php if ( $mode == "stream" ) { ?>
<td width="34%" align="center" class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=still&mid=<?= $mid ?>"><?= $zmSlangStills ?></a></td>
<?php } elseif ( canStream() ) { ?>
<td width="34%" align="center" class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&mode=stream&mid=<?= $mid ?>"><?= $zmSlangStream ?></a></td>
<?php } else { ?>
<td width="34%" align="center" class="text">&nbsp;</td>
<?php } ?>
<td width="33%" align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
<?php
	if ( $mode == "stream" )
	{
		$stream_src = ZM_PATH_ZMS."?monitor=$monitor[Id]&idle=".STREAM_IDLE_DELAY."&refresh=".STREAM_FRAME_DELAY."&ttl=".REFRESH_CYCLE;
		if ( isNetscape() )
		{
?>
<tr><td colspan="3" align="center"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=watch&mid=<?= $monitor[Id] ?>', 'zmWatch<?= $monitor[Name] ?>', <?= $monitor[Width]+$jws['watch']['w'] ?>, <?= $monitor[Height]+$jws['watch']['h'] ?> );"><img src="<?= $stream_src ?>" border="0" width="<?= $monitor[Width] ?>" height="<?= $monitor[Height] ?>"></a></td></tr>
<?php
		}
		else
		{
?>
<tr><td colspan="3" align="center"><applet code="com.charliemouse.cambozola.Viewer" archive="<?= ZM_PATH_CAMBOZOLA ?>" align="middle" width="<?= $monitor[Width] ?>" height="<?= $monitor[Height] ?>"><param name="url" value="<?= $stream_src ?>"></applet></td></tr>
<?php
		}
	}
	else
	{
?>
<tr><td colspan="3" align="center"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=watch&mid=<?= $monitor[Id] ?>', 'zmWatch<?= $monitor[Name] ?>', <?= $monitor[Width]+$jws['watch']['w'] ?>, <?= $monitor[Height]+$jws['watch']['h'] ?> );"><img src="<?= ZM_DIR_IMAGES.'/'.$monitor[Name] ?>.jpg" border="0" width="<?= $monitor[Width] ?>" height="<?= $monitor[Height] ?>"></a></td></tr>
<?php
	}
?>
</table>
</body>
</html>
