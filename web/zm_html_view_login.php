<html>
<head>
<title>ZM - <?= $zmSlangLogin ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
window.resizeTo( <?= $jws['console']['w'] ?>, <?= $jws['console']['h'] ?> );
</script>
</head>
<body>
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<form name="login_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="action" value="login">
<input type="hidden" name="view" value="postlogin">
<tr><td colspan="2" class="smallhead" align="center">ZoneMinder <?= $zmSlangLogin ?></td></tr>
<tr><td width="50%" class="text" align="right"><?= $zmSlangUsername ?></td><td width="50%" align="left" class="text"><input type="text" class="form" name="username" value="<?= isset($username)?$username:"" ?>" size="12"></tr>
<tr><td width="50%" class="text" align="right"><?= $zmSlangPassword ?></td><td width="50%" align="left" class="text"><input type="password" class="form" name="password" value="" size="12"></tr>
<tr><td colspan="2" align="center"><input type="submit" value="<?= $zmSlangLogin ?>" class="form"></td></tr>
</form>
</table>
</body>
</html>
