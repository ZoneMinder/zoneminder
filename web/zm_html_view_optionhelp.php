<html>
<head>
<title>ZM - Option Help</title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="96%">
<tr><td align="right" class="text"><a href="javascript: closeWindow();">Close</a></td></tr>
<tr><td align="left" class="smallhead"><?= $option ?></td></tr>
<tr><td class="text"><p class="text" align="justify"><?= $config[$option][Help] ?></p></td></tr>
</table>
</body>
</html>
