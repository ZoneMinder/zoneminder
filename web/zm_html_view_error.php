<html>
<head>
<title>ZM - <?= $zmSlangError ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function closeWindow()
{
	if ( window.parent != window )
		window.close();
	else
		top.window.close();
}
if ( window.parent != window )
{
	window.setTimeout( "window.close()", 30000 );
}
</script>
</head>
<body>
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<input type="hidden" name="action" value="login">
<tr><td colspan="2" class="smallhead" align="center">ZoneMinder <?= $zmSlangError ?></td></tr>
<tr><td colspan="2" class="text" align="center">&nbsp;</td></tr>
<tr><td colspan="2" class="text" align="center"><strong><?= $zmSlangYouNoPerms ?><br/><?= $zmSlangContactAdmin ?></strong></td></tr>
<tr><td colspan="2" class="text" align="center">&nbsp;</td></tr>
<tr><td colspan="2" class="text" align="center"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td></tr>
</table>
</body>
</html>
