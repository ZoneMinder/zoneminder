<html>
<head>
<title>ZM - <?= $zmSlangLogout ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table align="center" border="0" cellspacing="0" cellpadding="5" width="96%">
<form name="logout_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="action" value="logout">
<input type="hidden" name="view" value="login">
<tr><td colspan="2" class="smallhead" align="center">ZoneMinder <?= $zmSlangLogout ?></td></tr>
<tr><td colspan="2" class="text" align="center"><?= sprintf( $zmClangCurrentLogin, $user['Username'] ) ?></td></tr>
<tr><td align="center"><input type="submit" value="<?= $zmSlangLogout ?>" class="form"></td>
<td align="center"><input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow();"></td></tr>
</form>
</table>
</body>
</html>
