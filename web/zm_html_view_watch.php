<?php
	if ( !canView( 'Stream' ) )
	{
		$view = "error";
		break;
	}
	$result = mysql_query( "select * from Monitors where Id = '$mid'" );
	if ( !$result )
		die( mysql_error() );
	$monitor = mysql_fetch_assoc( $result );
?>
<html>
<head>
<title>ZM - <?= $monitor[Name] ?> - Watch</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
//opener.location.reload();
window.focus();
</script>
</head>
<frameset rows="<?= $monitor[Height]+32 ?>,16,*" border="1" frameborder="no" framespacing="0">
<frame src="<?= $PHP_SELF ?>?view=watchfeed&mid=<?= $monitor[Id] ?>" marginwidth="0" marginheight="0" name="MonitorStream" scrolling="no">
<frame src="<?= $PHP_SELF ?>?view=watchstatus&mid=<?= $monitor[Id] ?>" marginwidth="0" marginheight="0" name="MonitorStatus" scrolling="no">
<frame src="<?= $PHP_SELF ?>?view=watchevents&max_events=<?= MAX_EVENTS ?>&mid=<?= $monitor[Id] ?>" marginwidth="0" marginheight="0" name="MonitorEvents" scrolling="auto">
</frameset>
