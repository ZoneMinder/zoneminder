<?php
//
// ZoneMinder web user view file, $Date$, $Revision$
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

if ( !canEdit( 'System' ) )
{
	$view = "error";
	return;
}
$result = mysql_query( "select * from Users where Id = '$uid'" );
if ( !$result )
	die( mysql_error() );
if ( !($db_user = mysql_fetch_assoc( $result )) )
{
	$db_user = array();
	$db_user['Username'] = $zmSlangNewUser;
	$db_user['Enabled'] = 1;
}

$new_user = $db_user;

$yesno = array( 0=>$zmSlangNo, 1=>$zmSlangYes );
$nv = array( 'None'=>$zmSlangNone, 'View'=>$zmSlangView );
$nve = array( 'None'=>$zmSlangNone, 'View'=>$zmSlangView, 'Edit'=>$zmSlangEdit );
$bandwidths = array_merge( array( ""=>"" ), $bw_array );
$langs = array_merge( array( ""=>"" ), getLanguages() );

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangUser ?> - <?= $new_user['Username'] ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
<?php
if ( !empty($refresh_parent) )
{
?>
opener.location.reload(true);
<?php
}
?>
window.focus();
function validateForm(form)
{
	var errors = new Array();
	if ( !form.elements['new_user[Username]'].value )
	{
		errors[errors.length] = "You must supply a username";
	}
	if ( !form.elements['new_user[Password]'].value )
	{
		errors[errors.length] = "You must supply a password";
	}
	else
	{
		if ( !form.conf_password.value )
		{
			errors[errors.length] = "You must confirm the password";
		}
		else if ( form.elements['new_user[Password]'].value != form.conf_password.value )
		{
			errors[errors.length] = "The new and confirm passwords are different";
		}
	}
	if ( errors.length )
	{
		alert( errors.join( "\n" ) );
		return( false );
	}
	return( true );
}

function closeWindow()
{
	window.close();
}
</script>
</head>
<body>
<table border="0" cellspacing="0" cellpadding="4" width="100%">
<tr>
<td colspan="2" align="left" class="head"><?= $zmSlangUser ?> - &quot;<?= $new_user['Username'] ?>&quot;</td>
</tr>
<form name="user_form" method="post" action="<?= $PHP_SELF ?>" onsubmit="return validateForm( document.user_form )">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="user">
<input type="hidden" name="uid" value="<?= $uid ?>">
<tr><td align="right" class="text"><?= $zmSlangUsername ?></td><td align="left" class="text"><input type="text" name="new_user[Username]" value="<?= $new_user['Username'] ?>" size="16" class="form"></td></tr>
<tr><td align="right" class="text"><?= $zmSlangNewPassword ?></td><td align="left" class="text"><input type="password" name="new_user[Password]" value="<?= $new_user['Password'] ?>" size="16" class="form"></td></tr>
<tr><td align="right" class="text"><?= $zmSlangConfirmPassword ?></td><td align="left" class="text"><input type="password" name="conf_password" value="<?= $new_user['Password'] ?>" size="16" class="form"></td></tr>
<tr><td align="right" class="text"><?= $zmSlangLanguage ?></td><td align="left" class="text"><?= buildSelect( "new_user[Language]", $langs ) ?></td></tr>
<tr><td align="right" class="text"><?= $zmSlangEnabled ?></td><td align="left" class="text"><?= buildSelect( "new_user[Enabled]", $yesno ) ?></td></tr>
<tr><td align="right" class="text"><?= $zmSlangStream ?></td><td align="left" class="text"><?= buildSelect( "new_user[Stream]", $nv ) ?></td></tr>
<tr><td align="right" class="text"><?= $zmSlangEvents ?></td><td align="left" class="text"><?= buildSelect( "new_user[Events]", $nve ) ?></td></tr>
<tr><td align="right" class="text"><?= $zmSlangControl ?></td><td align="left" class="text"><?= buildSelect( "new_user[Control]", $nve ) ?></td></tr>
<tr><td align="right" class="text"><?= $zmSlangMonitors ?></td><td align="left" class="text"><?= buildSelect( "new_user[Monitors]", $nve ) ?></td></tr>
<tr><td align="right" class="text"><?= $zmSlangSystem ?></td><td align="left" class="text"><?= buildSelect( "new_user[System]", $nve ) ?></td></tr>
<tr><td align="right" class="text"><?= $zmSlangMaxBandwidth ?></td><td align="left" class="text"><?= buildSelect( "new_user[MaxBandwidth]", $bandwidths ) ?></td></tr>
<tr><td align="right" class="text"><?= $zmSlangRestrictedCameraIds ?></td><td align="left" class="text"><input type="text" name="new_user[MonitorIds]" value="<?= $new_user['MonitorIds'] ?>" size="16" class="form"></td></tr>
<tr>
<td align="right"><input type="submit" value="<?= $zmSlangSave ?>" class="form"></td><td align="left"><input type="button" value="<?= $zmSlangClose ?>" class="form" onClick="closeWindow()"></td>
</tr>
</table>
</body>
</html>
