<html>
<head>
<title>ZM - Restarting</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function closeWindow()
{
	window.close();
}
window.setTimeout( "window.close();", <?= 10000 ?> );
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="96%">
<tr><td align="right" class="text"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td></tr>
<tr><td align="center" class="smallhead">ZoneMinder - <?= $zmSlangRestarting ?></td></tr>
<tr><td align="center" class="text">Changes you have made to the configuration mean that ZoneMinder needs to restart. Please wait for a few seconds before applying any other changes.</td></tr>
</table>
</body>
</html>
