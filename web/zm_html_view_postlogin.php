<html>
<head>
<title>ZM - <?= $zmSlangLoggingIn ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
//window.resizeTo( <?= $jws['console']['w'] ?>, <?= $jws['console']['h'] ?> );
window.setTimeout( "window.location.replace('<?= $PHP_SELF ?>')", 250 );
</script>
</head>
<body>
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<input type="hidden" name="action" value="login">
<tr><td colspan="2" class="smallhead" align="center">ZoneMinder - <?= $zmSlangLogin ?></td></tr>
<tr><td colspan="2" class="text" align="center">&nbsp;</td></tr>
<tr><td colspan="2" class="text" align="center"><strong><?= $zmSlangLoggingIn ?></strong></td></tr>
<tr><td colspan="2" class="text" align="center">&nbsp;</td></tr>
</table>
</body>
</html>
