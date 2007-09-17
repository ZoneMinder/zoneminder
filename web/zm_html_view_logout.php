<?php
//
// ZoneMinder web logout view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangLogout ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
<?php
if ( ZM_USER_SELF_EDIT )
{
?>
function userWindow()
{
    var Name = window.open( '<?= $PHP_SELF ?>?view=user&uid=<?= $user['Id'] ?>', 'zmUser', 'resizeable,width=<?= $jws['user']['w'] ?>,height=<?= $jws['user']['h'] ?>' );
}
<?php
}
?>
function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<form name="logout_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="action" value="logout">
<input type="hidden" name="view" value="login">
<table align="center" border="0" cellspacing="0" cellpadding="5" width="96%">
<tr><td class="smallhead" align="center">ZoneMinder <?= $zmSlangLogout ?></td></tr>
<tr><td class="text" align="center"><?= sprintf( $zmClangCurrentLogin, $user['Username'] ) ?></td></tr>
</table>
<table align="center" border="0" cellspacing="0" cellpadding="5" width="96%">
<tr>
<td align="center"><input type="submit" value="<?= $zmSlangLogout ?>" class="form"></td>
<?php
if ( ZM_USER_SELF_EDIT )
{
?>
<td align="center"><input type="button" value="<?= $zmSlangConfig ?>" class="form" onClick="userWindow();"></td>
<?php
}
?>
<td align="center"><input type="button" value="<?= $zmSlangCancel ?>" class="form" onClick="closeWindow();"></td>
</tr>
</table>
</form>
</body>
</html>
