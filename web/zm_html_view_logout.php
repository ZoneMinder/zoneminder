<?php
//
// ZoneMinder web logout view file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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
<title>ZM - <?= $zmSlangLogout ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
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
